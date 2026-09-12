<?php

/**
 * 服务器端 deploy key 管理
 *
 * 服务对象：sync.items 中 method=git 且 repo 以 git@ 开头的条目（SSH 协议私有仓库）。
 * 远程 clone 此类仓库需要服务器持有 deploy key：在服务器生成密钥对，
 * 公钥由用户手动添加到仓库的 Settings → Deploy keys。
 *
 * - 每个仓库一把 ed25519 密钥：~/.ssh/deploy/<owner>_<repo>
 * - ~/.ssh/config 使用 deploy-managed 托管块，按 host 累积 IdentityFile，
 *   多项目共用服务器时合并不互相覆盖；托管块外的用户配置不动
 * - run(false)：生成密钥 + 更新 config + 输出公钥与添加指引
 * - run(true)：-T 验证模式，逐仓库用指定 key 测试认证（不重新生成）
 */
class GitSshManager
{
    protected const MANAGED_BEGIN = '# BEGIN deploy-managed';
    protected const MANAGED_END = '# END deploy-managed';

    public function __construct(
        protected DeploySSH $ssh,
        protected DeployConfig $config
    ) {
    }

    public function run(bool $testMode = false): void
    {
        $repos = $this->collectRepos();
        if (empty($repos)) {
            deploy_log('没有需要配置 deploy key 的同步项（仅 method=git 且 repo 为 git@ 开头）', 'warn');
            return;
        }

        if ($testMode) {
            $this->testAuth($repos);
        } else {
            $this->setupKeys($repos);
        }
    }

    /**
     * 收集需要 deploy key 的仓库（按 repo 去重）
     *
     * @return array<string, array{host: string, path: string}> repo 地址 => 解析结果
     */
    protected function collectRepos(): array
    {
        $repos = [];
        foreach ($this->config->getSyncItems() as $item) {
            if ($item['method'] !== 'git' || !str_starts_with($item['repo'], 'git@')) {
                continue;
            }
            $parsed = $this->parseRepo($item['repo']);
            if ($parsed === null) {
                deploy_log("无法解析仓库地址，跳过: {$item['repo']}", 'warn');
                continue;
            }
            $repos[$item['repo']] = $parsed;
        }
        return $repos;
    }

    /**
     * 解析 git@host:owner/repo(.git) 形式的仓库地址
     */
    protected function parseRepo(string $repo): ?array
    {
        if (preg_match('/^git@([^:\/]+):(.+?)(?:\.git)?$/', $repo, $m)) {
            return ['host' => $m[1], 'path' => $m[2]];
        }
        return null;
    }

    /**
     * @param array<string, array{host: string, path: string}> $repos
     */
    protected function setupKeys(array $repos): void
    {
        $projectName = $this->config->getProjectName();

        $this->ssh->exec('mkdir -p "$HOME/.ssh/deploy" && chmod 700 "$HOME/.ssh"', false);

        $newIdentityFiles = [];
        $results = [];

        foreach ($repos as $repo => $info) {
            $keyFile = '$HOME/.ssh/deploy/' . safe_name(str_replace('/', '_', $info['path']));
            $label = $info['host'] . '/' . $info['path'];

            $exists = trim($this->ssh->exec("[ -f {$this->dq($keyFile)} ] && echo YES || echo NO", false)) === 'YES';
            if ($exists) {
                deploy_log("密钥已存在，跳过生成: {$label}", 'info');
            } else {
                deploy_log("生成 deploy key: {$label}", 'step');
                $comment = 'deploy-' . safe_name($projectName) . '-' . str_replace('/', '_', $info['path']);
                $this->ssh->exec(
                    "ssh-keygen -t ed25519 -C {$this->rq($comment)} -f {$this->dq($keyFile)} -N '' -q"
                );
            }

            $results[$repo] = [
                'pub' => trim($this->ssh->exec("cat {$this->dq($keyFile . '.pub')}", false)),
            ];
            // config 中使用 ~ 形式（ssh_config 支持波浪号展开）
            $newIdentityFiles[$info['host']][] = '~' . substr($keyFile, strlen('$HOME'));
        }

        $this->updateConfig($newIdentityFiles);

        echo "\n";
        deploy_log('请将以下公钥添加到对应仓库的 Deploy keys（无需 write access）', 'step');
        foreach ($results as $repo => $data) {
            echo "\n  仓库: {$repo}\n";
            echo "  位置: Settings → Deploy keys → Add deploy key\n";
            echo "  公钥: {$data['pub']}\n";
        }
        echo "\n";
        deploy_log("添加完成后执行验证: php admin app:{$projectName} git:ssh -T", 'info');
    }

    /**
     * 更新 ~/.ssh/config 的 deploy-managed 托管块
     *
     * @param array<string, string[]> $newIdentityFiles host => IdentityFile（~ 形式）
     */
    protected function updateConfig(array $newIdentityFiles): void
    {
        $current = $this->ssh->exec('cat "$HOME/.ssh/config" 2>/dev/null', false);

        // 解析现有托管块中已有的 IdentityFile，多项目共用服务器时合并不丢失
        $existing = [];
        if (preg_match('/' . preg_quote(self::MANAGED_BEGIN, '/') . '\n(.*?)' . preg_quote(self::MANAGED_END, '/') . '\n?/s', $current, $m)) {
            if (preg_match_all('/^Host (\S+)\n((?:[ \t]+[^\n]*\n?)*)/m', $m[1], $blocks, PREG_SET_ORDER)) {
                foreach ($blocks as $block) {
                    if (preg_match_all('/^\s*IdentityFile\s+(.+)$/m', $block[2], $ids)) {
                        foreach ($ids[1] as $id) {
                            $existing[$block[1]][] = trim($id);
                        }
                    }
                }
            }
        }

        // 合并（host => IdentityFile 列表，新条目在前，去重保序）
        $hosts = [];
        foreach ($newIdentityFiles as $host => $keys) {
            foreach ($keys as $key) {
                if (!in_array($key, $hosts[$host] ?? [], true)) {
                    $hosts[$host][] = $key;
                }
            }
        }
        foreach ($existing as $host => $keys) {
            foreach ($keys as $key) {
                if (!in_array($key, $hosts[$host] ?? [], true)) {
                    $hosts[$host][] = $key;
                }
            }
        }

        $block = self::MANAGED_BEGIN . "\n";
        foreach ($hosts as $host => $keys) {
            $block .= "Host {$host}\n    User git\n";
            foreach ($keys as $key) {
                $block .= "    IdentityFile {$key}\n";
            }
            $block .= "    IdentitiesOnly yes\n    StrictHostKeyChecking accept-new\n";
        }
        $block .= self::MANAGED_END . "\n";

        if (str_contains($current, self::MANAGED_BEGIN)) {
            $newConfig = preg_replace(
                '/' . preg_quote(self::MANAGED_BEGIN, '/') . '\n.*?' . preg_quote(self::MANAGED_END, '/') . '\n?/s',
                $block,
                $current,
                1
            );
        } else {
            $newConfig = rtrim($current);
            $newConfig .= ($newConfig === '' ? '' : "\n\n") . $block;
        }

        $this->ssh->uploadContent($newConfig, '/tmp/deploy-ssh-config');
        $this->ssh->exec(
            'mkdir -p "$HOME/.ssh" && chmod 700 "$HOME/.ssh"'
            . ' && cat /tmp/deploy-ssh-config > "$HOME/.ssh/config"'
            . ' && chmod 600 "$HOME/.ssh/config" && rm -f /tmp/deploy-ssh-config',
            false
        );
        deploy_log('~/.ssh/config 已更新（deploy-managed 托管块）', 'ok');
    }

    /**
     * 逐仓库用指定 key 验证认证（公钥需已添加到 Deploy keys）
     *
     * @param array<string, array{host: string, path: string}> $repos
     */
    protected function testAuth(array $repos): void
    {
        deploy_log('=== Deploy key 认证验证 ===', 'step');

        foreach ($repos as $repo => $info) {
            $keyFile = '$HOME/.ssh/deploy/' . safe_name(str_replace('/', '_', $info['path']));
            $output = trim($this->ssh->exec(
                'ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o IdentitiesOnly=yes'
                . " -i {$this->dq($keyFile)} -T git@{$info['host']} 2>&1 || true",
                false
            ));

            if (preg_match('/Hi\s+\S+!/', $output)) {
                deploy_log("认证成功: {$repo}（{$output}）", 'ok');
            } elseif (str_contains($output, 'Permission denied')) {
                deploy_log("认证失败: {$repo} → 公钥尚未添加到 Deploy keys 或已失效", 'error');
            } else {
                deploy_log("响应异常: {$repo} → {$output}", 'warn');
            }
        }
    }

    /**
     * 远程路径双引号包裹（允许 $HOME 等 shell 变量展开）
     */
    protected function dq(string $value): string
    {
        return '"' . $value . '"';
    }

    /**
     * 远程 shell 引用（单引号转义，用于注释等字面量）
     */
    protected function rq(string $value): string
    {
        return "'" . str_replace("'", "'\\''", $value) . "'";
    }
}
