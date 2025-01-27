<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

const PROJECT_INIT_FILES = [
    // .env.example 手动生成
    '.env.example',
    'docker-compose.example.yaml',

    'docker/mysql/my.example.cnf',

    'docker/php/php.example.ini',
    'docker/php/supervisord.example.conf',
    'docker/nginx/sites/default.example.conf',
    // 下面两个文件属性于 /etc/nginx/conf.d/ 配置
    'docker/nginx/host.example.conf',

    'src/.env.example',
    'src/phpunit.example.xml',
    'src/config/config.example.php',
    'src/config/services.example.php',
    'src/config/migration.example.php',
];

const SSH_DATA = [
    'ip' => '',
    'port' => 22,
    'username' => '',
    'password' => '', // ssh2/sftp 密码登录
    'private_ssh_key' => '', // ssh2/sftp 证书登录
    'path' => 'path/of/phalcon-admin', // 当前项目在线上服务器所映射的目录
    'nginx' => '/etc/nginx/conf.d/',
    'certs' => ['key' => '', 'pem' => '', 'crt' => ''], // 上传到 nginx 目录的证书，要保存到当前目录；注意不要与远程证书文件同名，否则可能导致覆盖
    'hosts' => [], // 添加到 /etc/hosts 的域名列表；示例 [a1.test,www.a1.test]
    'logs' => [ // 需要下载的日志
        'docker/log/nginx/access.log',
        'docker/log/nginx/error.log',
        'docker/php/php_errors.log',
        ['src/storage/logs'] // 数组表示一个目录
    ],
    'backup' => [],// 其它需要备份的文件，在执行 php admin sync -d 时也会一同下载
    // 以下字段由系统自动更新，不需要更新
    'projects' => [],
];