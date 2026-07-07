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
    // 所有项目的默认 repo 地址（各项目可在自己的 server.php 中覆盖）
    'project' => [
        'repo' => 'https://github.com/tao996/phalcon-admin.git',                 // 默认仓库地址，如 git@github.com:user/phalcon-admin.git
        'branch' => 'main',
    ],
    // 所有项目的默认 env 变量
    'env' => [
        'TZ' => 'Asia/Shanghai',
        'REDIS_PASSWORD' => '123456',
        'MYSQL_PASSWORD' => '123456',
        'MYSQL_USER' => 'admin',
    ],
];
