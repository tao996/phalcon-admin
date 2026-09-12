<?php
// 测试用项目配置
return [
    'project' => [
        'name' => 'testproj',
        'path' => '/root/projects/testproj',
    ],
    'sync' => [
        'items' => [
            ['method' => 'git', 'repo' => 'git@example.com:test.git', 'branch' => 'main'],
            ['method' => 'ftp', 'path' => 'src/App/Projects/boyu'],
        ],
    ],
    'ssh' => [
        'port' => 2222, // 覆盖 server.php 中的 port
    ],
    'domains' => [
        'testproj.example.com',
    ],
    'env' => [
        'APP_NAME' => 'testproj',
        'MYSQL_DATABASE' => 'testproj_db',
    ],
    'config' => [
        'app.title' => 'Test Project',
        'app.origin' => 'https://testproj.example.com/',
    ],
    'hooks' => [
        'afterInit' => [
            'shell:php artisan key:generate',
        ],
    ],
];
