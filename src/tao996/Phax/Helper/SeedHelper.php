<?php

namespace Phax\Helper;

use Exception;
use PDO;
use Phax\Foundation\AppService;

/**
 * 基础数据（种子数据）执行器
 *
 * 与结构迁移（MigrationHelper / phalcon-migrations）互补，负责幂等地执行
 * 种子 SQL 文件：
 *
 *   - 默认扫描 App/Modules/<名称>/data/seed 与 App/Projects/<名称>/data/seed
 *     目录下的 .sql 文件（跨项目共享的基础数据，跟模块/项目仓库走）
 *   - --dir=xxx 可追加外部目录（如 deploy 上传的项目差异数据）
 *   - 每个文件以 scope + 文件名 + 内容 hash 记账于 seed_history 表，
 *     只执行新增或内容变化的文件，重复执行自动跳过
 *
 * 约定：seed 文件必须幂等（使用 REPLACE INTO / ON DUPLICATE KEY UPDATE），
 * 文件名建议带序号（001-xxx.sql）保证同目录内的执行顺序。
 *
 * 注意：MySQL 的 DDL 会隐式提交事务，含 DDL 的文件中途失败无法回滚，
 * 需要人工修正后重跑（ledger 未记账时会再次整体执行）。
 */
class SeedHelper
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? $this->createPdo();
    }

    /**
     * 收集待执行文件并按序执行
     *
     * @param array $params ['dir' => string 追加的外部 seed 目录（相对 PATH_ROOT 或绝对路径）]
     * @return array ['executed' => string[], 'skipped' => string[]]
     */
    public function run(array $params = []): array
    {
        $this->ensureLedgerTable();

        $groups = $this->collectFiles($params['dir'] ?? '');

        $executed = [];
        $skipped = [];
        foreach ($groups as $scope => $files) {
            foreach ($files as $file) {
                $hash = md5_file($file);
                if ($this->isApplied($scope, basename($file), $hash)) {
                    $skipped[] = $scope . '/' . basename($file);
                    continue;
                }

                echo "执行: {$scope}/" . basename($file) . "\n";
                $sql = file_get_contents($file);
                try {
                    $this->pdo->exec($sql);
                } catch (Exception $e) {
                    throw new Exception(
                        "seed 执行失败: {$scope}/" . basename($file) . ' — ' . $e->getMessage()
                        . "\n（请修正后重跑；ledger 未记账，修正后文件会整体重新执行）"
                    );
                }
                $this->markApplied($scope, basename($file), $hash);
                $executed[] = $scope . '/' . basename($file);
            }
        }

        return ['executed' => $executed, 'skipped' => $skipped];
    }

    /**
     * 收集 seed 文件：模块/项目目录（按迁移 order 排序）+ 外部目录
     *
     * @return array [scope => [文件绝对路径,...]]
     */
    private function collectFiles(string $extraDir): array
    {
        // 模块/项目排序复用迁移配置的 order（基础数据可能依赖 tao 等基础模块的表）
        $order = [];
        $migrationConfig = $this->loadMigrationConfig();
        foreach ($migrationConfig['order'] ?? [] as $item) {
            $order[] = str_contains((string)$item, ':') ? (string)$item : 'module:' . $item;
        }

        $groups = [];
        foreach (['Modules' => 'module', 'Projects' => 'project'] as $dirName => $type) {
            $base = PATH_ROOT . 'App/' . $dirName;
            foreach (glob($base . '/*/data/seed', GLOB_ONLYDIR) ?: [] as $dir) {
                // App/Modules/<名称>/data/seed → 取 <名称>
                $name = basename(dirname(dirname($dir)));
                $groups[$type . ':' . $name] = $dir;
            }
        }

        // 按 order 优先、其余键名排序
        $sorted = [];
        foreach ($order as $key) {
            if (isset($groups[$key])) {
                $sorted[$key] = $groups[$key];
                unset($groups[$key]);
            }
        }
        ksort($groups);
        $groups = $sorted + $groups;

        // 外部目录（deploy 上传的项目差异数据）
        if ($extraDir !== '') {
            $path = $this->resolveDir($extraDir);
            if (is_dir($path)) {
                $groups['external'] = $path;
            } else {
                echo "warn: seed 目录不存在，跳过: {$path}\n";
            }
        }

        $result = [];
        foreach ($groups as $scope => $dir) {
            $files = glob($dir . '/*.sql') ?: [];
            sort($files);
            if (!empty($files)) {
                $result[$scope] = $files;
            }
        }
        return $result;
    }

    private function loadMigrationConfig(): array
    {
        $path = PATH_CONFIG . 'migration.php';
        if (!file_exists($path)) {
            return [];
        }
        $data = require $path;
        return is_array($data) ? $data : [];
    }

    private function resolveDir(string $dir): string
    {
        if (str_starts_with($dir, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $dir)) {
            return rtrim($dir, '/\\');
        }
        return rtrim(PATH_ROOT . ltrim($dir, '/\\'), '/\\');
    }

    private function createPdo(): PDO
    {
        // 优先使用 migration.php 的 database 配置（与结构迁移同一目标库），缺省回退应用默认连接
        $config = $this->loadMigrationConfig();
        if (!empty($config['database']['dbname'])) {
            $db = $config['database'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'] ?? '127.0.0.1',
                $db['port'] ?? 3306,
                $db['dbname'],
                $db['charset'] ?? 'utf8mb4'
            );
            return new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        $dbDriver = AppService::config()->path('database.default');
        $store = AppService::config()->path('database.stores.' . $dbDriver)->toArray();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $store['host'] ?? '127.0.0.1',
            $store['port'] ?? 3306,
            $store['dbname'] ?? '',
            $store['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $store['username'] ?? '', $store['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $pdo;
    }

    private function ensureLedgerTable(): void
    {
        $this->pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS seed_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope VARCHAR(64) NOT NULL DEFAULT '',
  file VARCHAR(255) NOT NULL DEFAULT '',
  hash CHAR(32) NOT NULL DEFAULT '',
  executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_scope_file (scope, file)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    }

    private function isApplied(string $scope, string $file, string $hash): bool
    {
        $stmt = $this->pdo->prepare('SELECT hash FROM seed_history WHERE scope = ? AND file = ?');
        $stmt->execute([$scope, $file]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }
        if ($row['hash'] !== $hash) {
            echo "warn: {$scope}/{$file} 内容已变化（hash 不一致），将重新执行\n";
            return false;
        }
        return true;
    }

    private function markApplied(string $scope, string $file, string $hash): void
    {
        $stmt = $this->pdo->prepare(
            'REPLACE INTO seed_history (scope, file, hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$scope, $file, $hash]);
    }
}
