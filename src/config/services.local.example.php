<?php
// 直接本机运行（不是运行在 docker 中）
// 注意 redis 和 mysql 的密码
const redisConfig = [
    'lifetime' => 7200,
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '123456',
    'index' => 0,
    'prefix' => '',
    'persistent' => false
];
return [
    // https://docs.phalcon.io/5.0/en/cache
    'cache' => [
        'driver' => 'redis', // apcu, memcached, memory, redis, stream
        'stores' => [
            'redis' => array_merge(redisConfig, [
                'defaultSerializer' => 'Json',
            ]),
        ],
    ],
    'view' => [
        'pathDir' => PATH_STORAGE . 'cache/view', // volt 模板缓存位置
    ],
    'database' => [
        'default' => 'mysql',// env('DB_CONNECTION', 'mysql'),
        'stores' => [
            'mysql' => [
                'host' => '127.0.0.1',
                'port' => 3306,
                'dbname' => 'phalcon-admin',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ],
            ],
        ],
        // 是否记录 SQL 语句
        'log' => [
            'driver' => '', // file|profile，如果为空则表示不开启
            'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/sql_{Ym}.log' : 'logs/sql_{Ymd}.log')
        ],
    ],
    'redis' => redisConfig,
    'crypt' => [
        'key' => 'phalconX', // 建议修改此 key
        'padding' => '',
        'cipher' => 'aes-256-cfb',
    ],
    // https://docs.phalcon.io/5.0/en/logger
    'logger' => [
        'driver' => 'stream', // stream, syslog, noop
        'stores' => [
            'stream' => [
                'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/app_{Ym}.log' : 'logs/app_{Ymd}.log'),
                'name' => 'main',
                'level' => 'message',
            ],
        ]
    ],
    // https://docs.phalcon.io/5.0/en/session
    'session' => [
        'auto_start' => true,
        'driver' => 'redis', // stream, memcached, redis, noop(just for test),
        'cookie_lifetime' => 86400, // Cookie Max-Age（秒），必须与 session lifetime 一致
        'stores' => [
            'redis' => array_merge(redisConfig, [
                'username' => '',
// https://github.com/phalcon/cphalcon/blob/5.0.x/phalcon/Storage/Adapter/AbstractAdapter.zep
                'lifetime' => 86400, // 默认 24 小时；可根据需要调整，应与 config.php 中的值保持一致
            ]),
        ],
    ],
    // https://docs.phalcon.io/5.0/en/response#cookies
    'cookie' => [
        'key' => 'phalconX', // 加密密钥
        'secret' => false,
        'domain' => null,
    ],
    'flash' => 'direct',
    // https://docs.phalcon.io/5.0/en/db-models-metadata
    'metadata' => [
        'driver' => 'redis',
        'stores' => [
            'redis' => array_merge(redisConfig, [
                'lifetime' => 86400,
            ])
        ]
    ]
];