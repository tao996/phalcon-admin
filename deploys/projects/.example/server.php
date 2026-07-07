<?php

/**
 * 项目配置示例
 *
 * 拷贝为: deploys/projects/<项目名>/server.php
 * 然后修改真实值
 */
return [
    'project' => [
        'name' => 'myapp',                        // 项目名（同时也是容器名前缀）
        'repo' => 'git@github.com:user/repo.git', // 仓库地址
        'branch' => 'main',                       // 分支
        'path' => '/root/projects/myapp',         // 远程部署路径
        'modules' => [                            // src/App/Modules/ 下的子模块
            // '模块名' => '仓库地址',
            // 'demo' => 'git@github.com:user/module-demo.git',
            // 'tao'  => 'git@github.com:user/module-tao.git',
        ],
        // 宿主机模式时项目的 nginx 端口（可选，默认 8071，自动递增分配）
        // 'nginxPort' => 8071,
    ],
    'domains' => [                               // 域名列表（Router 转发用）
        'myapp.example.com',
    ],
    'env' => [                                   // 项目专属环境变量
        'APP_NAME' => 'myapp',
        'MYSQL_DATABASE' => 'myapp_db',
        // 'REDIS_PASSWORD' => 'myapp_redis_pwd',
        // 'MYSQL_PASSWORD' => 'myapp_db_pwd',
    ],
    'config' => [                                // 应用配置覆盖
        'app.title' => 'My App',
        'app.origin' => 'https://myapp.example.com/',
        'app.jwt.secret' => 'change-this-secret',
        'app.https' => true,
        'app.demo' => false,
        'app.superAdmin' => [1],
    ],
    // 钩子命令：初始化/更新后执行（可选）
    'hooks' => [
        'afterInit' => [
            // 'shell:php artisan migration',
            // 'shell:php artisan db:seed',
        ],
        'afterUpgrade' => [
            // 'shell:php artisan migration',
        ],
    ],
];
