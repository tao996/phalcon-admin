<?php

/**
 * 服务器连接配置模板
 *
 * 拷贝为 server.php 后修改真实值
 * cp deploys/server.example.php deploys/server.php
 *
 * 如果需要多台服务器，可创建不同文件，部署时指定: php deploy --server=staging
 */
return [
    'ssh' => [
        'host' => '',                 // IP 或域名
        'port' => 22,                 // SSH 端口（默认 22）
        'user' => 'root',             // SSH 用户
        // 认证方式（二选一）
        'password' => '',             // 密码认证
        // 'keyFile' => '~/.ssh/id_rsa',  // 密钥认证
        // 'keyPassphrase' => '',       // 密钥密码（可选）
    ],
    'docker' => [
        'network' => 'phalcon-shared', // 共享网络名
    ],
    'router' => [
        'containerName' => 'phalcon-router',
        'configDir' => '/etc/nginx-router/conf.d',
        'composePath' => '/root/router',
    ],
    // 用于为项目生成 app/server.php，可根据需要自行修改
    'application' => [
        'project' => [
            'name' => '{name}',                      // 项目名（同时也是容器名前缀）
            'path' => 'TODO::修改为你的远程部署地址',     // 远程部署路径，如 /data/xxx
            'modules' => [                            // src/App/Modules/ 下的子模块
                // '模块名' => '仓库地址',
                // 'demo' => 'git@github.com:user/module-demo.git',
                // 'tao'  => 'git@github.com:user/module-tao.git',
            ],
            // 默认仓库地址，如 git@github.com:tao996/phalcon-admin.git
            'repo' => 'https://github.com/tao996/phalcon-admin.git',
            'branch' => 'main',
            // 宿主机模式时项目的 nginx 端口（可选，默认 8071）
            'nginxPort' => 8071,
        ],
        'domains' => [ // 域名列表（Router 转发用）
            'myapp.example.com',
        ],
        // 所有项目的默认 env 变量
        'env' => [
            'TZ' => 'Asia/Shanghai',
            'APP_NAME' => '{name}', // docker compose 启动前缀
            'MYSQL_DATABASE' => '{name}_db',
            'MYSQL_USER' => 'admin',
            'MYSQL_PASSWORD' => '123456',
            'REDIS_PASSWORD' => '123456',
        ],
        'config' => [ // // 应用配置覆盖，详细注释查看 src/config/config.example.php
            'app' => [
                'title' => '站点名称',
                'origin' => 'https://myapp.example.com',
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
                'defaultApp' => [
                    'namespace' => '',
                ],
                'welcome' => '', // 后台首页
            ],
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
    ],
];
