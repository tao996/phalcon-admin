<?php

namespace Phax\Helper;

use Phalcon\Config\Config;
use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Migrations;
use Phax\Foundation\AppService;

class MigrationHelper
{
    public Parser $parser;

    public function __construct(public bool $consoleMessage = false)
    {

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
//        print Color::head('Documentation') . PHP_EOL;
//        print Color::colorize('  https://tao996.github.io/phalcon-admin-docs/#/zh-cn/migration') . PHP_EOL;
//        print Color::colorize('  https://docs.phalcon.io/latest/db-migrations/') . PHP_EOL . PHP_EOL;
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

    public function parser(array $argv): void
    {
        $this->parser = new Parser();
        $this->parser->parse($argv);

        $action = $this->parser->get(0);
        if (in_array($action, [null, 'help', 'h', '?'], true)) {
            $this->printHelperMessage();
            return;
        }
        // Keep migrations log in the database table rather than in file
        $migrationsInDb = $this->parser->has('log-in-db');
        // Timestamp based migration version
        $migrationsTsBased = $this->parser->has('ts-based');
        // Disable auto-increment (Generating only)
        $noAutoIncrement = $this->parser->has('no-auto-increment');
        // Skip referencedSchema inside generated migration (Generating only)
        $skipRefSchema = $this->parser->has('skip-ref-schema');
        // Wrap `SET FOREIGN_KEY_CHECKS` query before and after execution of a query (Running only)
        $skipForeignChecks = $this->parser->has('skip-foreign-checks');
        // Migration description (used for timestamp-based migration)
        $descr = $this->parser->get('descr');
        // Table to migrate. Table name or table prefix with an asterisk. Default: all
        $exportTablePrefix = $this->parser->get('table', '@');

        // 默认目录
        $migrationsDir = PATH_STORAGE_DATA . 'migration' . DIRECTORY_SEPARATOR;
        if (!file_exists($migrationsDir)) {
            mkdir($migrationsDir, 0777, true);
        }
        // 项目
        if ($project = $this->parser->get('p')) {
            $migrationsDir = PATH_APP_PROJECTS . $project . '/data/migration';
            if (!is_dir($migrationsDir) || !is_writable($migrationsDir)) {
                print_r($this->parser->getParsedCommands());
                throw new \Exception($migrationsDir . ' 目录不存在或不可写');
            }
            $exportTablePrefix = $project . '_*';
        }
        if ($module = $this->parser->get('m')) {
            $migrationsDir = PATH_APP_MODULES . $module . '/data/migration';
            if (!is_dir($migrationsDir) || !is_writable($migrationsDir)) {
                print_r($this->parser->getParsedCommands());
                throw new \Exception($migrationsDir . ' 目录不存在或不可写');
            }
            $exportTablePrefix = $module . '_*';
        }

        // 需要导出数据的数据表
        $exportDatasTable = '';
        if ($this->parser->has('datas')) {
            $exportDatasTable = $this->parser->get('datas', '@');
        }

        // 只有在 always 或 oncreate 时才会为表生成数据
        if ($howToRestoreData = $this->parser->get('data')) {
            if (in_array($action, ['generate', 'g'])) {
                if (!in_array($howToRestoreData, ['oncreate', 'always'])) {
                    throw new \Exception('--data should be always or oncreate');
                }
            } else {
                throw new \Exception('--data only run in generate|g');
            }
        }

        // 版本
        $version = $this->getParserValue(['version', 'v']);
        // 指定配置文件
        if ($configFile = $this->parser->get('config')) {
            $pathConfigFile = PATH_ROOT . ltrim($configFile);
            if (!file_exists($pathConfigFile)) {
                throw new \Exception($pathConfigFile . ' 配置文件不存在');
            } else {
                $dbConfig = include_once $pathConfigFile;
            }
        } // 检查默认配置文件
        elseif (file_exists(PATH_CONFIG . 'migration.php') && !in_array('skip', $this->parser->getParsedCommands())) {
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
//        ddd('数据库连接信息',$dbConfig);
        if (empty($dbConfig['database'])) {
            throw new \Exception('没有找到数据库配置信息');
        }

        $config = new Config($dbConfig);
        // 指定了保存目录
        if ($this->parser->has('directory')) {
            $migrationsDir = PATH_ROOT . ltrim($this->parser->get('directory'), '/');
        }
        $directory = $migrationsDir;

        switch ($action) {
            case 'generate':
            case 'g':
                $genParams = [
                    'directory' => $directory, // 项目位置 .phalcon 文件
                    'migrationsDir' => $directory,

                    'tableName' => $exportTablePrefix, // @ 表示全部导出全部的表
                    'exportData' => $howToRestoreData, // 以后如何恢复数据 always|oncreate
                    'exportDataFromTables' => $exportDatasTable,// 为哪些表生成数据
                    'version' => $version,
                    'force' => $this->parser->has('force'),
                    'noAutoIncrement' => $noAutoIncrement,
                    'config' => $config,
                    'descr' => $descr,
                    'verbose' => $this->parser->has('dry'),
                    'skip-ref-schema' => $skipRefSchema,
                ];
                Migrations::generate($genParams);
                break;
            case 'run':
            case 'r':
                $runParams = [
                    'directory' => $directory,
                    'migrationsDir' => $directory,
                    'tableName' => '',// $exportTablePrefix,
                    'force' => $this->parser->has('force'),
                    'tsBased' => $migrationsTsBased,
                    'config' => $config,
                    'version' => $version,
                    'migrationsInDb' => $migrationsInDb,
                    'verbose' => $this->parser->has('verbose'),
                    'skip-foreign-checks' => $skipForeignChecks,
                ];
                Migrations::run($runParams);
                break;
            case 'list':
            case 'l':
                $listParams = [
                    'directory' => $directory,
                    'migrationsDir' => $directory,
                    'tableName' => $exportTablePrefix,
                    'force' => $this->parser->has('force'),
                    'tsBased' => $migrationsTsBased,
                    'config' => $config,
                    'version' => $version,
                    'migrationsInDb' => $migrationsInDb,
                ];
                Migrations::listAll($listParams);
                break;

            default:
                throw new \Exception('不支持的操作. 使用 help, h 或 ? 查看全部可用命令');
        }
    }
}