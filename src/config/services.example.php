<?php

define('ENV_APP_NAME', env('APP_NAME') ? env('APP_NAME') . '-' : '');
return [
    // https://docs.phalcon.io/5.0/en/cache
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'), // apcu, memcached, memory, redis, stream
        'stores' => [
            'apcu' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200
            ],
            'redis' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'host' => env('REDIS_HOST', ENV_APP_NAME.'redis'),
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => env('REDIS_CACHE_INDEX', 0),
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'persistent' => env('CACHE_PERSISTENT', false)
            ],
            'stream' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'storageDir' => PATH_STORAGE . 'cache'
            ],
            'memory' => [ // warning: https://docs.phalcon.io/5.0/en/cache#memory
                'defaultSerializer' => 'Json',
                'lifetime' => 7200,
                'prefix' => env('CACHE_PREFIX', '_phc_')
            ],
            'memcached' => [
                'defaultSerializer' => 'Json',
                'lifetime' => 3600,
                'prefix' => env('CACHE_PREFIX', '_phc_'),
                'saslAuthData' => [
                    'user' => env('MEMCACHED_USER'),
                    'pass' => env("MEMCACHED_PASS"),
                ],
                'servers' => [
                    0 => [
                        'host' => env('MEMCACHED_HOST', 'memcached'),
                        'port' => (int)env('MEMCACHED_PORT', 11211),
                        'weight' => 1,
                    ],
                ],
            ]
        ],
    ],
    'view' => [
        'pathDir' => PATH_STORAGE . 'cache/view', // volt 模板缓存位置
    ],
    'database' => [
        'default' => 'mysql',// env('DB_CONNECTION', 'mysql'),
        'stores' => [
            'mysql' => [
                'host' => env('MYSQL_HOST', ENV_APP_NAME . 'mysql'),
                'port' => 3306,
                'dbname' => env('MYSQL_DATABASE', 'forge'),
                'username' => env('MYSQL_USER', 'forge'),
                'password' => env('MYSQL_PASSWORD', ''),
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
            'postgresql' => [
                'host' => env('POSTGRES_HOST', ENV_APP_NAME.'postgres'),
                'port' => 5432,
                'dbname' => env('POSTGRES_DB', 'forge'),
                'username' => env('POSTGRES_USER', 'forge'),
                'password' => env('POSTGRES_PASSWORD', ''),
                'schema' => env('POSTGRES_SCHEMA', 'public')
            ],
            'sqlite' => [
                // https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
                'dbname' => env('DB_DATABASE', '/var/www/database.db'),
            ]
        ],
        // 是否记录 SQL 语句
        'log' => [
            'driver' => env('SQL_LOG', ''), // file|profile，如果为空则表示不开启
            'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/sql_{Ym}.log' : 'logs/sql_{Ymd}.log')
        ],
    ],
    'redis' => [
        'host' => env('REDIS_HOST', ENV_APP_NAME. 'redis'),
        'port' => 6379,
        'auth' => env('REDIS_PASSWORD'),
        'index' => (int)env('REDIS_CACHE_INDEX', 0),
        'prefix' => env('REDIS_PREFIX', '_phx_'),
        'username' => env('REDIS_USERNAME'),
        'persistent' => env('REDIS_PERSISTENT', false),
    ],
    'crypt' => [
        'key' => env('CRYPT_KEY', 'phalconX'), // 建议修改此 key
        'padding' => env('CRYPT_PADDING', ''),
        'cipher' => env('CRYPT_CIPHER', 'aes-256-cfb'),
    ],
    // https://docs.phalcon.io/5.0/en/logger
    'logger' => [
        'driver' => env('LOGGER_DRIVER', 'stream'), // stream, syslog, noop
        'stores' => [
            'stream' => [
                'path' => PATH_STORAGE . (IS_DEBUG ? 'logs/app_{Ym}.log' : 'logs/app_{Ymd}.log'),
                'name' => env('LOG_NAME', 'main'),
                'level' => 'message',
            ],
            'syslog' => [
                'ident' => env('SYSLOG_IDENT', 'ident-name'),
                'level' => env('LOG_LEVEL', 'message'),
                'name' => env('LOG_NAME', 'main'),
            ],
            'noop' => [],
        ]
    ],
    // https://docs.phalcon.io/5.0/en/session
    'session' => [
        'auto_start' => true,
        'driver' => 'redis', // stream, memcached, redis, noop(just for test),
        'stores' => [
            'stream' => [
                'savePath' => PATH_STORAGE . 'cache/session',
            ],
            'memcached' => [
                'client' => [],
                'servers' => [
                    [
                        'host' => env('MEMCACHED_HOST', 'memcached'),
                        'port' => (int)env('MEMCACHED_PORT', 11211),
                        'weight' => 0,
                    ],
                ],
            ],
            'redis' => [
                'host' => env('REDIS_HOST', ENV_APP_NAME.'redis'),
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => (int)env('REDIS_SESSION_INDEX', 0),
                'prefix' => env('REDIS_PREFIX', '_ses_'),
                'username' => env('REDIS_USERNAME'),
                'persistent' => env('REDIS_PERSISTENT', false),
// https://github.com/phalcon/cphalcon/blob/5.0.x/phalcon/Storage/Adapter/AbstractAdapter.zep
                'lifetime' => 3600, // 测试，默認為 3600，与 php.ini 中保持一致
            ],
        ],
    ],
    // https://docs.phalcon.io/5.0/en/response#cookies
    'cookie' => [
        'key' => env('CRYPT_KEY', 'phalconX'), // 加密密钥
        'secret' => false,
        'domain' => null,
    ],
    'flash' => 'direct',
    // https://docs.phalcon.io/5.0/en/db-models-metadata
    'metadata' => [
        // use memory in dev
        // apcu|redis|stream|memory(测试)
        'driver' => env('METADATA_DRIVER', 'redis'),
        'stores' => [
            'stream' => [
                'metaDataDir' => PATH_STORAGE . 'cache',
            ],
            'apcu' => [
                'lifetime' => 86400,
                'prefix' => '_phm_',
            ],
            'memcached' => [
                'servers' => [
                    0 => [
                        'host' => env('MEMCACHED_HOST', ENV_APP_NAME.'memcached'),
                        'port' => 11211,
                        'weight' => 1,
                    ],
                ],
                'lifetime' => 86400,
                'prefix' => '_phm_',
            ],
            'redis' => [
                'host' => env('REDIS_HOST', ENV_APP_NAME.'redis'),
                'port' => 6379,
                'auth' => env('REDIS_PASSWORD'),
                'index' => 1,
                'lifetime' => 86400,
                'prefix' => env('REDIS_PREFIX', '_phm_'),
            ]
        ]
    ]
];