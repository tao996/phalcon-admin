<?php
return [
    // 服务器连接配置信息
    'ssh' => [
        'ip' => '', // IP 地址
        'username' => '', // 用户名
        'password' => '', // 密码
    ],
    'project' => [
        // 项目所在目录（绝对路径）
        'path' => '',
    ],
    // 项目的 server.php 是否与当前 server.php 合并，合并后，可以复用当前模板 server 所定义的 command 命令
    'commandMerge' => true,
    // 自定义的命令（命令是在连接远程服务器后才执行），允许用户在自己的项目中修改
    // 命令使用前缀区分命令类型
    // shell:待执行的 shell 命令
    // sftp:待执行的 sftp 命令
    'command' => [
        // 在子项目 server.php 中
        // 如果为 Array 表示自定义命令
        // 如果为 true 表示使用 模板 server.php 命令
        // 如果为 false 表示禁用命令
        'init' => [],
        'upgrade' => [],
        'upload' => [],
    ],
];