<?php
// 测试用服务器配置
return [
    'ssh' => [
        'host' => '1.2.3.4',
        'port' => 22,
        'user' => 'root',
        'password' => 'secret',
    ],
    'docker' => [
        'network' => 'phalcon-shared',
    ],
    'router' => [
        'containerName' => 'phalcon-router',
        'configDir' => '/etc/nginx-router/conf.d',
        'composePath' => '/root/router',
    ],
    'env' => [
        'TZ' => 'Asia/Shanghai',
        'REDIS_PASSWORD' => 'default_pwd',
        'MYSQL_PASSWORD' => 'default_pwd',
        'MYSQL_USER' => 'admin',
    ],
];
