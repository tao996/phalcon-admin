<?php

/**
 * SFTP 目录直传（增量）
 *
 * 用于同步不被 git 跟踪的目录（如 src/App/Projects/*，被主仓库 .gitignore 排除，
 * bundle 同步不会带上）。增量策略：记录每个目录最后一次成功同步的开始时间，
 * 下次只上传 mtime >= lastSync 的文件（只增改不删除）。
 *
 * lastSync 存于项目缓存 deploy/.cache/<project>.json 的 sftp.<目录>.lastSync：
 * - 只有本次目录内全部上传成功才更新（有失败则下次整个目录重传）
 * - 记录"开始时刻"而非结束时刻：同步期间被修改的文件下次会被重新上传（安全）
 *
 * 注意：新拷入但 mtime 早于 lastSync 的文件会被漏传，需要时用 full=1 强制全量。
 */
class SftpDirSync
{
    public function __construct(
        protected DeploySSH $ssh,
        protected string $localRepoRoot,
        protected string $remoteProjectPath,
        protected string $projectName
    ) {
    }

    /**
     * 同步多个目录（相对仓库根的路径，如 'src/App/Projects/boyu'）
     *
     * @param array  $dirs     目录列表
     * @param bool   $force    true 时忽略 lastSync 强制全量上传（excludes 仍然生效）
     * @param array  $excludes 不上传的相对路径前缀（目录或文件），对本调用中的所有目录生效
     */
    public function syncDirs(array $dirs, bool $force = false, array $excludes = []): void
    {
        $this->cleanupLegacyManifests();
        foreach ($dirs as $dir) {
            $this->syncDir((string)$dir, $force, $excludes);
        }
    }

    protected function syncDir(string $relDir, bool $force, array $excludes): void
    {
        $relDir = rtrim(str_replace('\\', '/', trim($relDir)), '/');
        $localDir = $this->localRepoRoot . '/' . $relDir;
        if (!is_dir($localDir)) {
            deploy_log("本地目录不存在，跳过: {$localDir}", 'warn');
            return;
        }

        $remoteDir = rtrim($this->remoteProjectPath, '/') . '/' . $relDir;
        $excludesSuffix = !empty($excludes) ? '，排除: ' . implode(', ', $excludes) : '';
        deploy_log("SFTP 同步目录: {$relDir} → {$remoteDir}{$excludesSuffix}", 'step');

        $cache = get_project_cache($this->projectName);
        $lastSync = $force ? null : ($cache['sftp'][$relDir]['lastSync'] ?? null);
        $threshold = $lastSync !== null ? strtotime($lastSync) : null;
        if ($force) {
            deploy_log('full 模式：忽略 lastSync，强制上传全部文件', 'info');
        } elseif ($threshold !== null) {
            deploy_log('增量同步：只上传 mtime >= ' . $lastSync . ' 的文件', 'info');
        }

        $files = [];
        $this->scanFiles($localDir, '', $excludes, $files);

        $runStart = time();
        $ensuredDirs = [];
        $uploaded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $rel => $info) {
            if ($threshold !== null && $info['mtime'] < $threshold) {
                $skipped++;
                continue;
            }

            $remoteFile = $remoteDir . '/' . $rel;
            $parent = dirname($remoteFile);
            if (!isset($ensuredDirs[$parent])) {
                $this->ssh->ensureDir($parent);
                $ensuredDirs[$parent] = true;
            }
            if ($this->ssh->upload($localDir . '/' . $rel, $remoteFile)) {
                $uploaded++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            deploy_log("目录同步存在 {$failed} 个失败，不更新 lastSync（下次重传整个目录）: {$relDir}", 'warn');
            return;
        }

        // 记录本次开始时刻：同步期间修改的文件下次会重新上传
        $cache['sftp'][$relDir] = ['lastSync' => date('c', $runStart)];
        set_project_cache($this->projectName, $cache);

        $total = count($files);
        deploy_log("目录完成: 共 {$total} 个文件，上传 {$uploaded}，跳过 {$skipped}（无变化）", 'ok');
    }

    /**
     * 递归收集本地文件：rel（相对路径，/ 分隔） => ['mtime' => int, 'size' => int]
     * 命中排除前缀的文件/目录直接跳过（目录级剪枝，不进入子树）
     */
    protected function scanFiles(string $dir, string $prefix, array $excludes, array &$files): void
    {
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $dir . '/' . $name;
            $rel = $prefix === '' ? $name : $prefix . '/' . $name;
            if (path_matches_excludes($rel, $excludes)) {
                continue;
            }
            if (is_dir($path)) {
                $this->scanFiles($path, $rel, $excludes, $files);
            } elseif (is_file($path)) {
                $files[$rel] = [
                    'mtime' => filemtime($path),
                    'size' => filesize($path),
                ];
            }
        }
    }

    /**
     * 清理旧版按文件清单（sftp-<project>-<md5>.json），已由项目缓存的 lastSync 取代
     */
    protected function cleanupLegacyManifests(): void
    {
        foreach (glob(deploy_base_path() . '/.cache/sftp-' . safe_name($this->projectName) . '-*.json') ?: [] as $file) {
            @unlink($file);
        }
    }
}
