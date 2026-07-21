<?php
/**
 * ┌─────────────────────────────────────────────────────────────┐
 * │                    Migration 配置说明                       │
 * └─────────────────────────────────────────────────────────────┘
 *
 * ════════════════════════════════════════════════════════════════
 *  一、Migration 是什么？
 * ════════════════════════════════════════════════════════════════
 *
 * Migration（数据库迁移）是一种版本化的数据库变更管理方式。
 * 它把数据库表结构（和可选的数据）导出为 PHP 文件，纳入 Git 管理，
 * 团队成员同步后执行 php artisan migration r 即可还原相同的数据库。
 *
 * 工作流程：
 *
 *   【开发阶段】
 *   1. 在本地 MySQL 中设计/修改表结构
 *   2. 执行 php artisan migration g          # 生成迁移文件
 *      或 php artisan migration g --scope=module:demo  # 只生成某个模块
 *   3. 生成的迁移文件（如 1.0.0/demo_profile.php）自动保存到
 *      对应 scope 的 data/migration/ 目录下
 *   4. 将迁移文件提交到 Git
 *
 *   【部署/同步阶段】
 *   5. 在其他环境（同事电脑、测试服务器）执行
 *      php artisan migration r                # 运行全部迁移
 *      php artisan migration r --scope=module:demo   # 只运行某个模块
 *   6. 代码自动在目标数据库中创建表、索引、外键等
 *
 *   【查看状态】
 *     php artisan migration l                  # 列出全部 scope 的已生成迁移
 *
 * ════════════════════════════════════════════════════════════════
 *  二、Scope（作用域）是什么？
 * ════════════════════════════════════════════════════════════════
 *
 * 每个 scope 代表一个独立的迁移单元，包含自己的迁移文件目录和表前缀。
 * 常见的 scope 类型：
 *
 *   module:demo    → 模块 demo，迁移文件在 App/Modules/demo/data/migration/
 *   module:tao     → 模块 tao
 *   module:yihe    → 独立扩展 yihe（需在配置文件中注册）
 *   project:xxx    → 项目（暂未启用）
 *
 * 整体设计：
 *
 *   src/
 *   ├── config/migration.php          ← 本配置文件
 *   └── App/Modules/
 *       ├── demo/data/migration/      ← demo 模块的迁移文件
 *       ├── tao/data/migration/       ← tao 模块的迁移文件
 *       └── yihe/data/migration/      ← yihe 扩展的迁移文件
 *
 * 无参数运行 php artisan migration g 时，会依次处理配置文件中
 * 定义的所有 scope。指定 --scope 则只处理一个。
 *
 * ════════════════════════════════════════════════════════════════
 *  三、版本号机制
 * ════════════════════════════════════════════════════════════════
 *
 * ts_based = true  （推荐）
 *   使用时间戳作为版本号，如 1718954000_demo_profile.php
 *   优点是多人并行开发时不会产生版本号冲突，各自的时间戳天然唯一
 *
 * ts_based = false
 *   使用递增版本号，如 1.0.0/ → 1.0.1/ → 1.0.2/
 *   适合单人开发，多人协作时容易冲突
 *
 * 无论哪种模式，重复执行 g 都会自动递增版本号。
 *
 * ════════════════════════════════════════════════════════════════
 *  四、数据导出
 * ════════════════════════════════════════════════════════════════
 *
 * 某些表包含"种子数据"（如系统配置、菜单、初始用户等），需要
 * 随迁移一起管理。在 scope 的 export 中列出这些表即可。
 *
 * 每次 g 会自动为这些表生成 batchInsert 代码，r 执行时自动插入。
 * 不在 export 中的表只导结构，不导数据。
 *
 * 注意：export 中的表必须是已经存在于数据库中的，否则生成会报错。
 *
 * ════════════════════════════════════════════════════════════════
 *  五、数据库连接
 * ════════════════════════════════════════════════════════════════
 *
 * database 段：可选。填写后优先使用此配置连接数据库。
 * 不填写则使用应用默认数据库连接（config.php 中的 database 配置）。
 * 通常在开发环境用应用默认库，在特殊环境时在此指定。
 *
 * ════════════════════════════════════════════════════════════════
 *  六、配置项一览
 * ════════════════════════════════════════════════════════════════
 *
 *   database       数据库连接（可选，不填用应用默认）
 *   ts_based       全局默认使用时间戳版本（true/false）
 *
 *   scopes         所有 scope 的定义，键名格式：
 *                  module:模块名   → 模块
 *                  project:项目名  → 项目
 *
 *   每个 scope 的配置项：
 *     directory       迁移文件保存目录（相对 PATH_ROOT）
 *                     如 App/Modules/demo/data/migration
 *     table_prefix    该 scope 管理的表前缀
 *                     如 demo_，则 g 时自动匹配所有 demo_ 开头的表
 *     ts_based        覆盖全局 ts_based（可选）
 *     export          需要导出数据的表（可选）
 *                     键为表名，值为导出方式 always（固定在 up 中插入）
 */

return [
    'database' => [
        'adapter' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'phalcon-admin-clean',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ],
    ],

    // 全局默认使用时间戳版本
    // 设为 false 则使用递增版本号（1.0.0 → 1.0.1 → ...）
    'ts_based' => true,

    // ──── scope 定义（全部迁移入口在此注册） ────
    'scopes' => [
        // demo 模块：框架自带
        'module:demo' => [
            'directory' => 'App/Modules/demo/data/migration',
            'table_prefix' => 'demo_',
        ],

        // tao 模块：框架自带，带种子数据
        'module:tao' => [
            'directory' => 'App/Modules/tao/data/migration',
            'table_prefix' => 'tao_',
            // 以下表的初始数据会随迁移一起导出和还原
            'export' => [
                'tao_cms_page' => 'always',
                'tao_open_config' => 'always',
                'tao_system_config' => 'always',
                'tao_system_menu' => 'always',
                'tao_system_node' => 'always',
                'tao_system_user' => 'always',
            ],
        ],

        // ──── 独立扩展在此追加 ────
        // 例如 yihe 扩展：
        // 'module:yihe' => [
        //     'directory' => 'App/Modules/yihe/data/migration',
        //     'table_prefix' => 'yihe_',
        //     'export' => [
        //         'yihe_config' => 'always',
        //     ],
        // ],
    ],
];
