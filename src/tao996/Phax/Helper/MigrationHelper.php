<?php

namespace Phax\Helper;

use Phalcon\Config\Config;
use Phalcon\Cop\Parser;
use Phalcon\Migrations\Migrations;
use Phax\Foundation\AppService;

class MigrationHelper
{
    private array $config = [];
    private array $scopes = [];

    public function __construct(
        public bool $consoleMessage = false
    ) {
        $this->config = $this->loadConfig();
        $this->scopes = $this->loadScopes();
    }

    // ==================== 公开 API ====================

    /**
     * 生成迁移文件
     *
     * @param array $params 支持键：
     *   - scope        : string  必需，scope 名称
     *   - tables       : string  覆盖表名/前缀（默认从 scope 配置读取）
     *   - datas        : string|array|null  覆盖导出表（默认从 scope.export 读取）
     *   - data_method  : string  覆盖导出方法
     *   - version      : string
     *   - force        : bool
     *   - config       : string
     *   - no_auto_increment : bool
     *   - skip_ref_schema   : bool
     *   - descr        : string
     *   - dry          : bool
     */
    public function generate(array $params = []): array
    {
        try {
            $scope = $params['scope'] ?? '';
            if (!$scope || !isset($this->scopes[$scope])) {
                throw new \Exception('scope 无效：' . $scope);
            }
            $sc = $this->scopes[$scope];

            $migrationsDir = $this->resolveDir($sc['directory'] ?? 'storage/data/migration');

            $tablePrefix = $sc['table_prefix'] ?? '';
            $tableName   = $params['tables'] ?? ($tablePrefix ? $tablePrefix . '*' : '@');
            $tsBased     = $params['ts_based'] ?? $sc['ts_based'] ?? $this->config['ts_based'] ?? true;

            // 导出数据：优先 params，其次 scope.export 配置
            $exportDataFromTables = null;
            $restoreDataMethod    = null;
            if (array_key_exists('datas', $params)) {
                $datas = $params['datas'];
                if ($datas === true || $datas === '' || $datas === '@') {
                    $exportDataFromTables = ['@'];
                } elseif (is_string($datas)) {
                    $exportDataFromTables = [$datas];
                } elseif (is_array($datas)) {
                    $exportDataFromTables = $datas;
                }
                $restoreDataMethod = $params['data_method'] ?? null;
            } else {
                $scopeExport = $sc['export'] ?? [];
                if (!empty($scopeExport)) {
                    $exportDataFromTables = array_keys($scopeExport);
                    // null：让 shouldExportDataFromTable 单独控制，避免全局 always 短路
                    $restoreDataMethod = null;
                }
            }

            $dbConfig = $this->resolveDbConfig($params['config'] ?? null, 'generate', !empty($params['skip']));

            ob_start();
            Migrations::generate([
                'directory' => $migrationsDir,
                'migrationsDir' => [$migrationsDir],
                'tableName' => $tableName,
                'exportDataFromTables' => $exportDataFromTables,
                'exportData' => $restoreDataMethod,
                'version' => $params['version'] ?? null,
                'force' => !empty($params['force']),
                'tsBased' => $tsBased,
                'noAutoIncrement' => !empty($params['no_auto_increment']),
                'config' => $dbConfig,
                'descr' => $params['descr'] ?? null,
                'verbose' => !empty($params['dry']),
                'skip-ref-schema' => !empty($params['skip_ref_schema']),
            ]);
            $output = ob_get_clean();

            return ['success' => true, 'output' => $output, 'message' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'output' => '', 'message' => $e->getMessage()];
        }
    }

    /**
     * 运行迁移
     */
    public function run(array $params = []): array
    {
        try {
            $scope = $params['scope'] ?? '';
            if (!$scope || !isset($this->scopes[$scope])) {
                throw new \Exception('scope 无效：' . $scope);
            }
            $sc = $this->scopes[$scope];

            $migrationsDir = $this->resolveDir($sc['directory'] ?? 'storage/data/migration');
            // phalcon-migrations 的 generate() 忽略 tsBased 选项，
            // 始终使用递增版本号（如 1.0.0），故 list/run 也用 false 与其保持一致
            $tsBased       = false;
            $dbConfig      = $this->resolveDbConfig($params['config'] ?? null, 'run', !empty($params['skip']));

            ob_start();
            Migrations::run([
                'directory' => $migrationsDir,
                'migrationsDir' => [$migrationsDir],
                'tableName' => '',
                'force' => !empty($params['force']),
                'tsBased' => $tsBased,
                'config' => $dbConfig,
                'version' => $params['version'] ?? null,
                'migrationsInDb' => !empty($params['log_in_db']),
                'verbose' => !empty($params['verbose']),
                'skip-foreign-checks' => !empty($params['skip_foreign_checks']),
            ]);
            $output = ob_get_clean();

            return ['success' => true, 'output' => $output, 'message' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'output' => '', 'message' => $e->getMessage()];
        }
    }

    /**
     * 列出迁移文件
     */
    public function list(array $params = []): array
    {
        try {
            $scope = $params['scope'] ?? '';
            if (!$scope || !isset($this->scopes[$scope])) {
                throw new \Exception('scope 无效：' . $scope);
            }
            $sc = $this->scopes[$scope];

            $migrationsDir = $this->resolveDir($sc['directory'] ?? 'storage/data/migration');
            // phalcon-migrations 的 generate() 忽略 tsBased 选项，
            // 始终使用递增版本号（如 1.0.0），故 list/run 也用 false 与其保持一致
            $tsBased       = false;
            $dbConfig      = $this->resolveDbConfig($params['config'] ?? null, 'list', !empty($params['skip']));
            $tablePrefix   = $sc['table_prefix'] ?? '';
            $tableName     = $params['tables'] ?? ($tablePrefix ? $tablePrefix . '*' : '@');

            ob_start();
            Migrations::listAll([
                'directory' => $migrationsDir,
                'migrationsDir' => [$migrationsDir],
                'tableName' => $tableName,
                'force' => !empty($params['force']),
                'tsBased' => $tsBased,
                'config' => $dbConfig,
                'version' => $params['version'] ?? null,
                'migrationsInDb' => !empty($params['log_in_db']),
            ]);
            $output = ob_get_clean();

            return ['success' => true, 'output' => $output, 'message' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'output' => '', 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取帮助信息
     */
    public function help(): string
    {
        $text = '';
        $text .= 'Migration — 数据库迁移工具' . PHP_EOL;
        $text .= '  php artisan migration <action> [--scope=xxx]' . PHP_EOL . PHP_EOL;

        $text .= 'Actions:' . PHP_EOL;
        $text .= '  g | generate              生成全部 scope 的迁移文件' . PHP_EOL;
        $text .= '  g --scope=xxx             生成指定 scope 的迁移文件' . PHP_EOL;
        $text .= '  r | run                   运行全部 scope 的迁移' . PHP_EOL;
        $text .= '  r --scope=xxx             运行指定 scope 的迁移' . PHP_EOL;
        $text .= '  l | list                  列出全部 scope 的迁移' . PHP_EOL;
        $text .= '  help | h                  显示此帮助' . PHP_EOL . PHP_EOL;

        $text .= 'Scopes（在 config/migration.php 中定义）：' . PHP_EOL;
        if (!empty($this->scopes)) {
            $maxLen = max(array_map('strlen', array_keys($this->scopes)));
            foreach ($this->scopes as $key => $sc) {
                $dir = $sc['directory'] ?? '';
                $text .= '  ' . str_pad($key, $maxLen + 2) . $dir . PHP_EOL;
            }
        } else {
            $text .= '  （无）' . PHP_EOL;
        }
        $text .= PHP_EOL;

        $text .= 'Examples:' . PHP_EOL;
        $text .= '  php artisan migration g                    # 生成所有 scope' . PHP_EOL;
        $text .= '  php artisan migration g --scope=module:demo # 仅生成 demo' . PHP_EOL;
        $text .= '  php artisan migration r                    # 运行所有迁移' . PHP_EOL;
        $text .= '  php artisan migration l                    # 列出所有迁移' . PHP_EOL;

        return $text;
    }

    // ==================== CLI 入口 ====================

    /**
     * CLI 入口
     * 支持：g/g --scope=xx / r / r --scope=xx / l / help
     */
    public function parser(array $argv): void
    {
        $parser = new Parser();
        $parser->parse($argv);

        $action = $parser->get(0);
        if (in_array($action, [null, 'help', 'h', '?'], true)) {
            $this->printHelperMessage();
            return;
        }

        // 确定要处理的范围
        $scope = $this->parseScope($parser);
        $scopes = $scope ? [$scope] : array_keys($this->scopes);

        if (empty($scopes)) {
            echo "没有配置任何 scope，请在 config/migration.php 中定义。\n";
            return;
        }

        // 路由到对应方法
        $handler = match (true) {
            in_array($action, ['generate', 'g']) => fn(string $s) => $this->generate(['scope' => $s]),
            in_array($action, ['run', 'r'])      => fn(string $s) => $this->run(['scope' => $s]),
            in_array($action, ['list', 'l'])     => fn(string $s) => $this->list(['scope' => $s]),
            default => null,
        };

        if (!$handler) {
            throw new \Exception('不支持的操作：' . $action . '。使用 g, r, l, help');
        }

        // 依次执行
        $hasError = false;
        foreach ($scopes as $s) {
            if (count($scopes) > 1) {
                echo "\n=== {$s} ===\n";
            }
            $result = $handler($s);
            if ($result['success']) {
                echo $result['output'];
            } else {
                $hasError = true;
                echo 'Error: ' . $result['message'] . "\n";
            }
        }

        if ($hasError) {
            throw new \Exception('部分 scope 执行失败，请检查错误信息');
        }
    }

    // ==================== 内部方法 ====================

    /**
     * 加载配置文件
     */
    private function loadConfig(): array
    {
        $path = PATH_CONFIG . 'migration.php';
        if (file_exists($path)) {
            $data = require $path;
            return is_array($data) ? $data : [];
        }
        return [];
    }

    /**
     * 读取 scope 列表：仅从配置文件中读取
     */
    private function loadScopes(): array
    {
        return $this->config['scopes'] ?? [];
    }

    /**
     * 从 CLI Parser 解析 scope 参数
     */
    private function parseScope(Parser $parser): ?string
    {
        $scope = $parser->get('scope');
        if (!$scope) {
            if ($module = $parser->get('m')) {
                $scope = 'module:' . $module;
            } elseif ($project = $parser->get('p')) {
                $scope = 'project:' . $project;
            }
        }
        return $scope ?: null;
    }

    /**
     * 解析迁移目录：相对路径拼接 PATH_ROOT
     */
    private function resolveDir(string $dir): string
    {
        $path = PATH_ROOT . ltrim($dir, '/\\');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * 解析数据库配置
     */
    private function resolveDbConfig(?string $configFile, string $action, bool $skip = false): Config
    {
        $dbConfig = [];

        if ($configFile) {
            $path = PATH_ROOT . ltrim($configFile);
            if (!file_exists($path)) {
                throw new \Exception('配置文件不存在：' . $path);
            }
            $data = require $path;
            if (is_array($data)) {
                $dbConfig = $data;
            }
        } elseif (!$skip && !empty($this->config['database'])) {
            $dbConfig = ['database' => $this->config['database']];
            if (!empty($this->config[$action])) {
                $dbConfig['database'] = array_merge($dbConfig['database'], $this->config[$action]);
            }
        }

        if (empty($dbConfig['database'])) {
            $dbDriver = AppService::config()->path('database.default');
            $dbConfig = [
                'database' => array_merge(
                    ['adapter' => $dbDriver],
                    AppService::config()->path('database.stores.' . $dbDriver)->toArray()
                ),
            ];
        }

        if (empty($dbConfig['database'])) {
            throw new \Exception('没有找到数据库配置信息');
        }

        return new Config($dbConfig);
    }

    private function printHelperMessage(): void
    {
        if (!$this->consoleMessage) {
            return;
        }
        echo $this->help();
    }
}
