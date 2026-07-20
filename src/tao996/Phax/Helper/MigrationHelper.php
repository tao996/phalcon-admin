<?php

namespace Phax\Helper;

use Phalcon\Config\Config;
use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Migrations;
use Phax\Foundation\AppService;
/*
// CLI 方式（不变）
$helper = new MigrationHelper(true);
$helper->parser($argv);

// Web 方式（新增）
$helper = new MigrationHelper();
$result = $helper->execute([
    'action'  => 'generate',
    'project' => 'city',
    'force'   => true,
]);
if ($result['success']) {
    echo $result['output']; // 或返回给前端
} else {
    echo $result['message']; // 错误信息
}
 */
class MigrationHelper
{
    public Parser $parser;

    public function __construct(public bool $consoleMessage = false)
    {

    }

    /**
     * 获取纯文本帮助信息（无 ANSI 颜色，适合 Web/CLI 通用展示）
     */
    public function getHelpMessage(): string
    {
        $text = '';
        $text .= 'Help:' . PHP_EOL;
        $text .= '  Generates/Run a Migration for phalcon-admin' . PHP_EOL . PHP_EOL;

        $text .= 'Usage: Generate a Migration' . PHP_EOL;
        $text .= '  migration generate|g            # generate all tables, default save in storage/data/migration;' . PHP_EOL;
        $text .= '  examples:' . PHP_EOL;
        $text .= '  migration g --p=city                             # generate for project city, save in app/Http/Projects/xxx/data/migration' . PHP_EOL;
        $text .= '  migration g --m=demo --datas                     # generate for modules demo, save in app/Modules/xxx/data/migration' . PHP_EOL;
        $text .= '  migration g --table=demo_,tao_                   # generate tables structure start with demo_ or tao_' . PHP_EOL;
        $text .= '  migration g --table=demo_,tao_ --datas           # generate tables structure and data start with demo_ and tao_' . PHP_EOL;
        $text .= '  migration g --table=demo_,tao_ --datas=demo_*    # generate tables structure start with demo_ and tao_, but data only start with demo_' . PHP_EOL . PHP_EOL;

        $text .= 'Usage: Run a Migration' . PHP_EOL;
        $text .= '  migration run|r        # import tables(structure/data) from generated migrations' . PHP_EOL;
        $text .= '  examples:' . PHP_EOL;
        $text .= '  migration r                # migration from storage/data/migration' . PHP_EOL;
        $text .= '  migration r -p=demo        # migration from src/App/Projects/demo/data/migration' . PHP_EOL;
        $text .= '  migration r -m=demo        # migration from src/App/Modules/demo/data/migration' . PHP_EOL . PHP_EOL;

        $text .= 'Usage: List all available migrations' . PHP_EOL;
        $text .= '  migration list|l' . PHP_EOL;
        $text .= '  examples:' . PHP_EOL;
        $text .= '  migration l                # list migration from storage/data/migration' . PHP_EOL;
        $text .= '  migration -m=demo l        # list migration from src/App/Modules/demo/data/migration' . PHP_EOL;
        $text .= '  migration -p=demo l        # list migration from src/App/Projects/demo/data/migration' . PHP_EOL . PHP_EOL;

        $text .= 'Arguments:' . PHP_EOL;
        $text .= '  help' . "\t" . 'Shows this help text' . PHP_EOL . PHP_EOL;

        $text .= 'Options:' . PHP_EOL;
        foreach ($this->getPossibleParams() as $parameter => $description) {
            $text .= '  --' . sprintf('%-30s', $parameter);
            if (is_array($description)) {
                foreach ($description as $index => $desc) {
                    $text .= ($index == 0 ? '' : str_repeat(' ', 33)) . $desc . PHP_EOL;
                }
            } else {
                $text .= $description . PHP_EOL;
            }
        }

        return $text;
    }

    private function printHelperMessage(): void
    {
        if (!$this->consoleMessage) {
            return;
        }
        print Color::head('Help:') . PHP_EOL;
        print Color::colorize('  Generates/Run a Migration for phalcon-admin') . PHP_EOL . PHP_EOL;

        print Color::head('Usage: Generate a Migration') . PHP_EOL;
        print Color::colorize(
                '  migration generate|g            # generate all tables, default save in storage/data/migration;',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  examples:',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration g --p=city                             # generate for project city, save in app/Http/Projects/xxx/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration g --m=demo --datas                     # generate for modules demo, save in app/Modules/xxx/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration g --table=demo_,tao_                   # generate tables structure start with demo_ or tao_',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration g --table=demo_,tao_ --datas           # generate tables structure and data start with demo_ and tao_',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration g --table=demo_,tao_ --datas=demo_*    # generate tables structure start with demo_ and tao_, but data only start with demo_',
                Color::FG_GREEN
            ) . PHP_EOL . PHP_EOL;

        print Color::head('Usage: Run a Migration') . PHP_EOL;
        print Color::colorize(
                '  migration run|r        # import tables(structure/data) from generated migrations',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  examples:',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration r                # migration from storage/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration r -p=demo        # migration from src/App/Projects/demo/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration r -m=demo        # migration from src/App/Modules/demo/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL . PHP_EOL;

        print Color::head('Usage: List all available migrations') . PHP_EOL;
        print Color::colorize('  migration list|l', Color::FG_GREEN) . PHP_EOL;
        print Color::colorize('  examples:', Color::FG_GREEN) . PHP_EOL;
        print Color::colorize(
                '  migration l                # list migration from storage/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration -m=demo l        # list migration from src/App/Modules/demo/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL;
        print Color::colorize(
                '  migration -p=demo l        # list migration from src/App/Projects/demo/data/migration',
                Color::FG_GREEN
            ) . PHP_EOL . PHP_EOL;

        print Color::head('Arguments:') . PHP_EOL;
        print Color::colorize('  help', Color::FG_GREEN);
        print Color::colorize("\tShows this help text") . PHP_EOL . PHP_EOL;

        $this->printParameters($this->getPossibleParams());
    }

    private function printParameters(array $parameters): void
    {
        print Color::head('Options:') . PHP_EOL;
        foreach ($parameters as $parameter => $description) {
            echo Color::colorize(' --' . sprintf('%-30s', $parameter), Color::FG_GREEN);

            if (is_array($description)) {
                foreach ($description as $index => $desc) {
                    echo ($index == 0 ? '' : str_repeat(' ', 33)) . Color::colorize($desc) . PHP_EOL;
                }
            } else {
                echo Color::colorize($description) . PHP_EOL;
            }
        }
    }

    private function getPossibleParams(): array
    {
        return [
            'p=ProjectName' => '导出 app/Projects 目录下指定的项目 (save in app/Projects/xxx/data/migration)',
            'm=ModuleName' => '导出 app/Modules  目录下指定的模块 (save in app/Modules/xxx/data/migration)',
            'directory=str' => 'migration 数据所在的目录，默认为 storage/data/migration',
            'table[=str]' => '导出表结构，默认为 @；如果需要导出多个表，则使用逗号进行分割；支持前缀 xxx_；会被 --p|--m 覆盖;(不需要添加*号)',
            'datas' => '同时导出表数据，默认为跟随 table 参数；如果需要导出多个表，则使用逗号进行分割；支持前缀 xxx_*;(需要添加*号)',
            'version=str' => '当前版本号，默认为 1.0.0',
            'data=always|oncreate' => 'oncreate(import data if table not exists); how to restore data when `migrate run`',
            'config=str' => 'read to db config file when generate or run action;',
            'force' => 'Forces to overwrite existing migrations',
            'ts-based' => 'Timestamp based migration version',
            'help' => 'Shows this help [optional]',
        ];
    }

    private function getParserValue(array $keys, mixed $default = null)
    {
        foreach ($keys as $key) {
            if ($this->parser->has($key)) {
                return $this->parser->get($key, $default);
            }
        }
        return $default;
    }

    /**
     * 核心执行逻辑：接受关联数组参数，执行迁移操作
     * 同时适配 Web（$_GET/$_POST）和 CLI（$argv）两种场景
     *
     * 支持的参数键：
     *   - action       : string  操作名 (generate|g|run|r|list|l|help)
     *   - project|p    : string  项目名
     *   - module|m     : string  模块名
     *   - table        : string  表名/前缀，默认 '@'（全部）
     *   - datas        : string  需要导出数据的表（传递空字符串 = 全部，null = 不导出）
     *   - version|v    : string  版本号
     *   - config       : string  配置文件路径
     *   - directory    : string  迁移目录
     *   - force        : bool    是否覆盖已存在的迁移
     *   - ts_based     : bool    使用时间戳版本
     *   - no_auto_increment : bool  禁用自增
     *   - skip_ref_schema   : bool  跳过引用 schema
     *   - skip_foreign_checks : bool  跳过外键检查
     *   - descr        : string  迁移描述
     *   - log_in_db    : bool    在数据库表中记录迁移日志
     *   - verbose      : bool    详细输出
     *   - dry          : bool    演练模式（不实际执行）
     *   - data         : string  数据恢复方式 (always|oncreate)
     *   - skip         : bool    跳过默认 migration.php 配置
     *
     * @param array $params 参数数组
     * @return array ['success' => bool, 'output' => string, 'message' => string]
     */
    public function execute(array $params): array
    {
        $action = $params['action'] ?? '';

        if (in_array($action, ['', 'help', 'h', '?'], true)) {
            return [
                'success' => true,
                'output' => $this->getHelpMessage(),
                'message' => '',
            ];
        }

        try {
            // 提取并规整参数
            $project = $params['project'] ?? $params['p'] ?? null;
            $module = $params['module'] ?? $params['m'] ?? null;
            $table = $params['table'] ?? '@';
            $datas = array_key_exists('datas', $params)
                ? ($params['datas'] ?: '@')
                : null;
            $version = $params['version'] ?? $params['v'] ?? null;
            $configFile = $params['config'] ?? null;
            $directory = $params['directory'] ?? null;
            $force = !empty($params['force']);
            $tsBased = !empty($params['ts_based']);
            $noAutoIncrement = !empty($params['no_auto_increment']);
            $skipRefSchema = !empty($params['skip_ref_schema']);
            $skipForeignChecks = !empty($params['skip_foreign_checks']);
            $descr = $params['descr'] ?? null;
            $migrationsInDb = !empty($params['log_in_db']);
            $verbose = !empty($params['verbose']);
            $dry = !empty($params['dry']);
            $howToRestoreData = $params['data'] ?? null;
            $skip = !empty($params['skip']);

            // 验证操作
            if (!in_array($action, ['generate', 'g', 'run', 'r', 'list', 'l'], true)) {
                throw new \Exception('不支持的操作. 使用 help, h 或 ? 查看全部可用命令');
            }

            // 默认目录
            $migrationsDir = PATH_STORAGE_DATA . 'migration' . DIRECTORY_SEPARATOR;
            if (!file_exists($migrationsDir)) {
                mkdir($migrationsDir, 0777, true);
            }

            $exportTablePrefix = $table;

            // 项目
            if ($project) {
                $migrationsDir = PATH_APP_PROJECTS . $project . '/data/migration';
                if (!is_dir($migrationsDir) || !is_writable($migrationsDir)) {
                    throw new \Exception($migrationsDir . ' 目录不存在或不可写');
                }
                $exportTablePrefix = $project . '_*';
            }

            if ($module) {
                $migrationsDir = PATH_APP_MODULES . $module . '/data/migration';
                if (!is_dir($migrationsDir) || !is_writable($migrationsDir)) {
                    throw new \Exception($migrationsDir . ' 目录不存在或不可写');
                }
                $exportTablePrefix = $module . '_*';
            }

            // 需要导出数据的数据表
            $exportDatasTable = $datas ?? '';

            // 只有在 always 或 oncreate 时才会为表生成数据
            if ($howToRestoreData) {
                if (in_array($action, ['generate', 'g'])) {
                    if (!in_array($howToRestoreData, ['oncreate', 'always'])) {
                        throw new \Exception('--data should be always or oncreate');
                    }
                } else {
                    throw new \Exception('--data only run in generate|g');
                }
            }

            // 指定配置文件
            $dbConfig = [];
            if ($configFile) {
                $pathConfigFile = PATH_ROOT . ltrim($configFile);
                if (!file_exists($pathConfigFile)) {
                    throw new \Exception($pathConfigFile . ' 配置文件不存在');
                } else {
                    $dbConfig = include_once $pathConfigFile;
                }
            } // 检查默认配置文件
            elseif (file_exists(PATH_CONFIG . 'migration.php') && !$skip) {
                $data = include_once PATH_CONFIG . 'migration.php';
                if (!empty($data) && !empty($data['database'])) {
                    $dbConfig = ['database' => $data['database']];
                    if (!empty($data[$action])) {
                        $dbConfig['database'] = array_merge($dbConfig['database'], $data[$action]);
                    }
                    if (in_array($action, ['g', 'generate']) && !empty($dbConfig['g'])) {
                        $dbConfig['database'] = array_merge($dbConfig, $dbConfig['g']);
                    } elseif (in_array($action, ['r', 'run']) && !empty($dbConfig['r'])) {
                        $dbConfig['database'] = array_merge($dbConfig['database'], $dbConfig['r']);
                    }
                }
            }
            // 如果没有找到数据库连接配置，则从默认的配置中获取
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

            $config = new Config($dbConfig);

            // 指定了保存目录
            if ($directory) {
                $migrationsDir = PATH_ROOT . ltrim($directory, '/');
            }

            // 捕获 Migrations 的直接输出
            ob_start();
            switch ($action) {
                case 'generate':
                case 'g':
                    $genParams = [
                        'directory' => $migrationsDir,
                        'migrationsDir' => $migrationsDir,
                        'tableName' => $exportTablePrefix,
                        'exportData' => $howToRestoreData,
                        'exportDataFromTables' => $exportDatasTable,
                        'version' => $version,
                        'force' => $force,
                        'noAutoIncrement' => $noAutoIncrement,
                        'config' => $config,
                        'descr' => $descr,
                        'verbose' => $dry,
                        'skip-ref-schema' => $skipRefSchema,
                    ];
                    Migrations::generate($genParams);
                    break;
                case 'run':
                case 'r':
                    $runParams = [
                        'directory' => $migrationsDir,
                        'migrationsDir' => $migrationsDir,
                        'tableName' => '',
                        'force' => $force,
                        'tsBased' => $tsBased,
                        'config' => $config,
                        'version' => $version,
                        'migrationsInDb' => $migrationsInDb,
                        'verbose' => $verbose,
                        'skip-foreign-checks' => $skipForeignChecks,
                    ];
                    Migrations::run($runParams);
                    break;
                case 'list':
                case 'l':
                    $listParams = [
                        'directory' => $migrationsDir,
                        'migrationsDir' => $migrationsDir,
                        'tableName' => $exportTablePrefix,
                        'force' => $force,
                        'tsBased' => $tsBased,
                        'config' => $config,
                        'version' => $version,
                        'migrationsInDb' => $migrationsInDb,
                    ];
                    Migrations::listAll($listParams);
                    break;
            }
            $output = ob_get_clean();

            return [
                'success' => true,
                'output' => $output,
                'message' => 'operation completed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * CLI 入口：解析 $argv 并委托给 execute()
     * 保持与原调用方兼容
     */
    public function parser(array $argv): void
    {
        $this->parser = new Parser();
        $this->parser->parse($argv);

        $action = $this->parser->get(0);
        if (in_array($action, [null, 'help', 'h', '?'], true)) {
            $this->printHelperMessage();
            return;
        }

        // 从 Parser 中提取参数，委托给 execute()
        $params = [
            'action' => $action,
            'project' => $this->parser->get('p'),
            'module' => $this->parser->get('m'),
            'table' => $this->parser->get('table', '@'),
            'datas' => $this->parser->has('datas')
                ? $this->parser->get('datas', '@')
                : null,
            'version' => $this->getParserValue(['version', 'v']),
            'config' => $this->parser->get('config'),
            'directory' => $this->parser->get('directory'),
            'force' => $this->parser->has('force'),
            'ts_based' => $this->parser->has('ts-based'),
            'no_auto_increment' => $this->parser->has('no-auto-increment'),
            'skip_ref_schema' => $this->parser->has('skip-ref-schema'),
            'skip_foreign_checks' => $this->parser->has('skip-foreign-checks'),
            'descr' => $this->parser->get('descr'),
            'log_in_db' => $this->parser->has('log-in-db'),
            'verbose' => $this->parser->has('verbose'),
            'dry' => $this->parser->has('dry'),
            'data' => $this->parser->get('data'),
            'skip' => $this->parser->has('skip'),
        ];

        $result = $this->execute($params);

        if ($result['success']) {
            echo $result['output'];
        } else {
            print_r($this->parser->getParsedCommands());
            throw new \Exception($result['message']);
        }
    }
}
