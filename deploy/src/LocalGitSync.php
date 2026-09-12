<?php

/**
 * 本地 → 远程 Git 同步（git bundle 方式）
 *
 * 背景：远程服务器不再配置 GitHub 密钥，由本地开发机（总是最新）生成 git bundle，
 * 经现有 SFTP 通道上传后，远程执行 git fetch + reset 完成更新。
 *
 * 与 GitHelper 的区别：
 *   GitHelper   — 远程 git clone/pull（需要远程能访问仓库，如 GitHub）
 *   LocalGitSync — 本地打包 + 上传 + 远程 fetch（远程无需任何仓库凭据）
 */
class LocalGitSync
{
    /**
     * @param DeploySSH $ssh 已连接的远程通道
     * @param string $localRepoRoot 本地主仓库根目录
     */
    public function __construct(
        protected DeploySSH $ssh,
        protected string $localRepoRoot
    ) {
    }

    /**
     * 同步单个仓库到远程
     *
     * @param string $localRepo 本地仓库路径，或 'main' 表示主仓库
     * @param string $remotePath 远程仓库路径
     * @param string $branch 分支名
     * @param string $tag 临时文件标识（避免并发冲突）
     */
    public function sync(string $localRepo, string $remotePath, string $branch, string $tag): void
    {
        if ($localRepo === 'main') {
            $localRepo = $this->localRepoRoot;
        }

        [$bundleFile, $bundleRef] = $this->createBundle($localRepo, $branch, $tag);
        $remoteBundle = '/tmp/deploy-' . safe_name($tag) . '.bundle';

        try {
            $this->ssh->upload($bundleFile, $remoteBundle);

            $remotePathQ = $this->rq($remotePath);
            $bundleQ = $this->rq($remoteBundle);
            $fetchRefQ = $this->rq($bundleRef);
            $branchQ = $this->rq($branch);

            $exists = trim($this->ssh->exec(
                "[ -d {$remotePathQ}/.git ] && echo 'YES' || echo 'NO'",
                false
            )) === 'YES';

            if ($exists) {
                deploy_log("更新远程仓库: {$remotePath}", 'step');
                $cmd = "cd {$remotePathQ} && git fetch {$bundleQ} {$fetchRefQ} && git reset --hard FETCH_HEAD && echo __SYNC_OK__";
            } else {
                deploy_log("初始化远程仓库: {$remotePath}", 'step');
                $this->ssh->ensureDir($remotePath);
                $cmd = "cd {$remotePathQ} && git init -q && git fetch {$bundleQ} {$fetchRefQ} && git checkout -q -b {$branchQ} FETCH_HEAD && echo __SYNC_OK__";
            }

            if (!str_contains($this->ssh->exec($cmd), '__SYNC_OK__')) {
                throw new RuntimeException("远程 git 同步失败: {$remotePath}");
            }
        } finally {
            if (file_exists($bundleFile)) {
                @unlink($bundleFile);
            }
            $this->ssh->exec("rm -f " . $this->rq($remoteBundle), false);
        }
    }

    /**
     * 同步单仓库到本地目录（不经过 SSH/SFTP）
     */
    public function syncLocal(string $localRepo, string $targetPath, string $branch, string $tag): void
    {
        if ($localRepo === 'main') {
            $localRepo = $this->localRepoRoot;
        }

        [$bundleFile, $bundleRef] = $this->createBundle($localRepo, $branch, $tag);

        try {
            if (is_dir($targetPath . '/.git')) {
                deploy_log("更新本地仓库: {$targetPath}", 'step');
                $this->git($targetPath, ['fetch', $bundleFile, $bundleRef]);
                $this->git($targetPath, ['reset', '--hard', 'FETCH_HEAD']);
            } else {
                deploy_log("初始化本地仓库: {$targetPath}", 'step');
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
                $this->git($targetPath, ['init', '-q']);
                $this->git($targetPath, ['fetch', $bundleFile, $bundleRef]);
                $this->git($targetPath, ['checkout', '-q', '-b', $branch, 'FETCH_HEAD']);
            }
        } finally {
            if (file_exists($bundleFile)) {
                @unlink($bundleFile);
            }
        }
    }

    /**
     * 在本地生成自包含的 git bundle，返回本地文件路径
     *
     * 浅克隆（.git/shallow）无法用 git bundle 打包完整历史，会生成“看似完整、
     * 实际缺对象”的坏 bundle。此处在浅克隆时用 HEAD 的 tree 造一个无父提交，
     * 再打包该提交——自包含、无需网络、不改动本地仓库。
     */
    protected function createBundle(string $repo, string $branch, string $tag): array
    {
        if (!is_dir($repo . '/.git')) {
            throw new RuntimeException("本地不是 git 仓库: {$repo}");
        }

        $ref = $this->resolveRef($repo, $branch);
        $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'deploy-' . safe_name($tag) . '-' . bin2hex(random_bytes(4)) . '.bundle';

        $tempRef = null;
        if ($this->isShallow($repo)) {
            deploy_log('本地为浅克隆，生成快照 bundle', 'info');
            $tree = $this->git($repo, ['rev-parse', $ref . '^{tree}']);
            $object = $this->git($repo, [
                '-c', 'user.name=deploy',
                '-c', 'user.email=deploy@local',
                'commit-tree', $tree, '-m', 'deploy snapshot from ' . $ref,
            ]);
            // bundle 需要具名 ref，不能直接打包裸 SHA
            $tempRef = 'refs/deploy-snapshot/' . safe_name($tag) . '-' . bin2hex(random_bytes(4));
            $this->git($repo, ['update-ref', $tempRef, $object]);
            $bundleRef = $tempRef;
        } else {
            $bundleRef = $ref;
        }

        try {
            $this->git($repo, ['bundle', 'create', $file, $bundleRef]);
        } finally {
            if ($tempRef !== null) {
                exec('git -C ' . escapeshellarg($repo) . ' update-ref -d ' . escapeshellarg($tempRef) . ' 2>&1');
            }
        }

        if (!file_exists($file)) {
            throw new RuntimeException("生成 bundle 失败: {$file}");
        }

        $size = round(filesize($file) / 1024, 1);
        deploy_log("已生成 bundle: {$ref} ({$size} KiB)", 'ok');

        return [$file, $bundleRef];
    }

    /**
     * 是否为浅克隆
     */
    protected function isShallow(string $repo): bool
    {
        $output = [];
        $code = 0;
        exec(
            'git -C ' . escapeshellarg($repo) . ' rev-parse --is-shallow-repository 2>&1',
            $output,
            $code
        );
        return $code === 0 && trim(implode('', $output)) === 'true';
    }

    /**
     * 执行本地 git 命令并返回输出，失败抛异常
     */
    protected function git(string $repo, array $args): string
    {
        $cmd = 'git -C ' . escapeshellarg($repo);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg($arg);
        }

        $output = [];
        $code = 0;
        exec($cmd . ' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new RuntimeException(
                'git ' . implode(' ', $args) . ' 失败: ' . implode("\n", $output)
            );
        }

        return trim(implode("\n", $output));
    }

    /**
     * 解析可用引用：优先分支，其次 HEAD
     */
    protected function resolveRef(string $repo, string $branch): string
    {
        $output = [];
        $code = 0;
        exec(
            'git -C ' . escapeshellarg($repo)
            . ' rev-parse --verify ' . escapeshellarg('refs/heads/' . $branch) . ' 2>&1',
            $output,
            $code
        );
        return $code === 0 ? $branch : 'HEAD';
    }

    /**
     * 远程 shell 引用（远程为 Linux，使用单引号转义，避免 Windows escapeshellarg 产生双引号）
     */
    protected function rq(string $value): string
    {
        return "'" . str_replace("'", "'\\''", $value) . "'";
    }
}
