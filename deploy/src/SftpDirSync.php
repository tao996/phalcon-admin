<?php

/**
 * SFTP 目录直传（增量）
 *
 * 用于同步不被 git 跟踪的目录（如 src/App/Projects/*，被主仓库 .gitignore 排除，
 * bundle 同步不会带上）。按 mtime + size 清单做增量：只上传新增/修改的文件，
 * 本地已删除的文件不删除远程对应文件。
 *
 * 清单存于 deploy/.cache/sftp-<project>-<md5(dir)>.json，按目录隔离。
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
     * @param array $dirs 目录列表
     * @param bool  $force true 时忽略清单强制全量上传
     */
    public function syncDirs(array $dirs, bool $force = false): void
    {
        foreach ($dirs as $dir) {
            $this->syncDir((string)$dir, $force);
        }
    }

    protected function syncDir(string $relDir, bool $force): void
    {
        $relDir = rtrim(str_replace('\\', '/', trim($relDir)), '/');
        $localDir = $this->localRepoRoot . '/' . $relDir;
        if (!is_dir($localDir)) {
            deploy_log("本地目录不存在，跳过: {$localDir}", 'warn');
            return;
        }

        $remoteDir = rtrim($this->remoteProjectPath, '/') . '/' . $relDir;
        deploy_log("SFTP 同步目录: {$relDir} → {$remoteDir}", 'step');

        $manifest = $this->loadManifest($relDir);
        $files = [];
        $this->scanFiles($localDir, '', $files);

        $ensuredDirs = [];
        $uploaded = 0;
        $skipped = 0;

        foreach ($files as $rel => $info) {
            $prev = $manifest['files'][$rel] ?? null;
            if (!$force && $prev !== null
                && $prev['mtime'] === $info['mtime'] && $prev['size'] === $info['size']) {
                $skipped++;
                continue;
            }

            $remoteFile = $remoteDir . '/' . $rel;
            $parent = dirname($remoteFile);
            if (!isset($ensuredDirs[$parent])) {
                $this->ssh->ensureDir($parent);
                $ensuredDirs[$parent] = true;
            }
            $this->ssh->upload($localDir . '/' . $rel, $remoteFile);
            $manifest['files'][$rel] = $info;
            $uploaded++;
        }

        if ($uploaded > 0) {
            $this->saveManifest($relDir, $manifest);
        }

        $total = count($files);
        deploy_log("目录完成: 共 {$total} 个文件，上传 {$uploaded}，跳过 {$skipped}（无变化）", 'ok');
    }

    /**
     * 递归收集本地文件：rel（相对路径，/ 分隔） => ['mtime' => int, 'size' => int]
     */
    protected function scanFiles(string $dir, string $prefix, array &$files): void
    {
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $dir . '/' . $name;
            $rel = $prefix === '' ? $name : $prefix . '/' . $name;
            if (is_dir($path)) {
                $this->scanFiles($path, $rel, $files);
            } elseif (is_file($path)) {
                $files[$rel] = [
                    'mtime' => filemtime($path),
                    'size' => filesize($path),
                ];
            }
        }
    }

    protected function manifestPath(string $relDir): string
    {
        $cacheDir = deploy_base_path() . '/.cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        return $cacheDir . '/sftp-' . safe_name($this->projectName) . '-' . md5($relDir) . '.json';
    }

    protected function loadManifest(string $relDir): array
    {
        $file = $this->manifestPath($relDir);
        if (!file_exists($file)) {
            return ['dir' => $relDir, 'files' => []];
        }
        $data = json_decode(file_get_contents($file), true);
        // 目录路径变化或清单损坏时全部重传
        if (!is_array($data) || ($data['dir'] ?? '') !== $relDir || !isset($data['files'])) {
            return ['dir' => $relDir, 'files' => []];
        }
        return $data;
    }

    protected function saveManifest(string $relDir, array $manifest): void
    {
        $manifest['dir'] = $relDir;
        $manifest['_updatedAt'] = date('c');
        file_put_contents(
            $this->manifestPath($relDir),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
