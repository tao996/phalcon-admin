<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/
const HELP_INFO = <<<HELP
-y : 默认情况下如果 nginx 目录下存在着站点的配置文件，则不会自动生成

php admin quick : 快速开始，只生成最基本的配置文件

php admin local   
    -init       : 初始化本地环境；如果已经存在，则会自动备份到 backup/local_xxx 目录下
        -port=Num   : nginx 反代的端口号；默认值为 -port=8071
        -y          : 覆盖已存在的文件
    -backup     : 备份当前项目的配置文件到 backup/local_xxx 目录下
    -clear      : 删除由 `php admin local -init` 生成的文件，必须添加 -y，否则只是预览删除文件；删除前会自动备份到 sync/local_xxx 目录下

php admin sync
    -dir[=Str]   : 操作的目录，默认为 default；
    -c           : 将配置文件生成到指定目录下，并同时生成一份 ssh.php 文件
        -y           : 强制覆盖已经存在的配置文件（ssh.php 不会被覆盖）
    -ping        : 检测 ssh.php 是否可以与远程环境通信
    -u[=Str]     : 将 -dir 目录下的配置文件上传到 ssh.php 中服务器指定的项目位置；
                   默认值为 *，表示同时上传 app, nginx, hosts
                   app   上传应用相关的配置文件
                   nginx 上传 nginx 配置文件及证书
                   hosts 修改 /etc/hosts 文件
    -urm[=Str]   : 移除 -u 所上传的文件，默认值为 *，可选值为 app|nginx|hosts
    -d           : 下载远程项目的配置文件到指定的本地目录，保存时到自动追加日期
    -log         : 下载远程项目日志文件到指定目录下，保存时自动追加日期
    -proj=Str    : 推送指定  project 到远程目录，同时在 ssh.php 中记录 推送的时间
        -git     : 同时在项目下执行 git pull 更新项目
HELP;

const PROJECT_INIT_FILES = [
    // .env.example 手动生成
    '.env.example',
    'docker-compose.example.yaml',

    'docker/mysql/my.example.cnf',

    'docker/php/php.example.ini',
    'docker/php/supervisord.example.conf',
    'docker/nginx/sites/default.example.conf',
    'docker/nginx/assets.example.conf',
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