<?php

/**
 * 项目配置示例
 *
 * 拷贝为: deploys/projects/<项目名>/server.php
 * 然后修改真实值
 *
 * 公共配置（repo、branch、ssh 等）在 deploys/server.php 中定义，项目自动继承。
 * 如果项目需要覆盖，在此文件中同名键即可。
 */
return [
    'project' => [
        'name' => 'myapp',                        // 项目名（同时也是容器名前缀）
        'path' => '/root/projects/myapp',         // 远程部署路径
        'modules' => [                            // src/App/Modules/ 下的子模块
            // '模块名' => '仓库地址',
            // 'demo' => 'git@github.com:user/module-demo.git',
            // 'tao'  => 'git@github.com:user/module-tao.git',
        ],
        // repo 和 branch 继承自 deploys/server.php，如需覆盖取消注释：
         'repo' => 'https://github.com/tao996/phalcon-admin.git',
         'branch' => 'main',
        // 宿主机模式时项目的 nginx 端口（可选，默认 8071）
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
    'config' => [
        'app' => [
            'title' => 'Your app name',
            'origin' => 'https://myapp.example.com/',
            'jwt' => ['secret' => 'change-this-secret'],
            'superAdmin' => [1],
            'default' => '',
            'defaultApp' => [
                'namespace' => '', // 默认首页，示例 App\Modules\yihe\Controllers
            ],
        ],// 应用配置覆盖
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
    // Docker 镜像覆盖（可选，缺省使用 compose 模板中的默认值）
    'docker' => [
        'images' => [
            // 'php' => 'registry.example.com/phalcon:5.13.0',
            // 'nginx' => 'registry.example.com/nginx:stable-alpine',
            // 'mysql' => 'registry.example.com/mysql:8.1.0',
            // 'redis' => 'registry.example.com/redis:7.2-alpine',
        ],
    ],
];
