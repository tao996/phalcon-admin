<?php

/**
 * 项目配置 — 由 deploy 工具自动创建
 */
return [
    'project' => [
        'name' => 'yihe',
        'path' => '/data/yihe',
        'repo' => 'git@github.com:tao996/phalcon-admin.git',
        'branch' => 'main',
        'modules' => [
            'yihe' => 'git@github.com:tao996/yihe.git',
        ],
        'nginxPort' => 8073,
    ],
    'domains' => [
        'yihe.gu19.cn'
    ],
    'env' => [
        'APP_NAME' => 'yihe',
        'MYSQL_DATABASE' => 'yihe_db',
        'MYSQL_USER' => 'admin',
        'MYSQL_PASSWORD' => '6258ce2bc519b9ef',
        'REDIS_PASSWORD' => '41f68bc683a0f29e',
    ],
    'config' => [
        'app' => [
            'title' => 'Yihe Admin',
            'origin' => 'https://yihe.gu19.cn/',
            'assets' => [
                'cdn' => '', 'hosts' => [], 'min' => false,
            ],
            'demo' => [
                'open' => false, 'admin' => ['account' => 'admin', 'password' => '123456'],
            ],
            'test' => [
                'open' => false,
            ],
            'superAdmin' => [1],
            'default' => '',
            'welcome' => '/m/yihe/index/welcome',
            'defaultApp' => [
                'namespace' => 'App\Modules\yihe\Controllers',
            ],
        ],
    ],
];