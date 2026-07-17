<?php

// 本地开发配置示例
$data = include __DIR__ . '/services.local.example.php';
// $data = include __DIR__ . '/services.example.php';
$data['crypt']['key'] = '123456'; // 只能修改一次，否则加密的账号信息解密失败
// only for dev
$data['metadata']['driver'] = 'memory';
$data['database']['log']['driver'] = 'file';
$data['database']['stores']['mysql']['dbname'] = 'phalcon-admin-test';
$data['app'] = [
    'title' => '网站标题', // 应用标题/名称
//    'origin' => 'http://localhost:8072/', // 必须以 / 结尾，用于生成链接地址；默认从 $_SERVER['HTTP_HOST'] 或其它参数中获取
    'welcome' => '/m/geo/index/welcome', // 后台加载首页
    'defaultApp' => [
        'namespace' => 'App\Modules\geo\Controllers', // 前台默认打开的首页
    ],
    'superAdmin' => [1],
    'assets' => [
        'cdn' => '',
        'hosts' => [],
        'min' => false,
    ],
    'demo' => [
        'open' => true,
        'admin' => [
            'account' => 'admin',
            'password' => '123456'
        ],
    ],
    'test' => [
        'open' => true,
        'tokens' => [
            'tao' => 1,
            'house' => 1000,
        ],
    ],
];

return $data;

/*
// 可以直接创建 config.php 然后保存以下内容
$data = include __DIR__ . '/config-dev.example.php';

$data['database']['stores']['mysql']['dbname'] = 'phalcon-admin-test';
$data['app'] = array_merge_deep($data['app'],[
    'title' => 'WebSite Dev', // 应用标题/名称
    'welcome' => '/m/abc/index/welcome',
    'defaultApp' => [
        'namespace' => 'App\Modules\abc\Controllers',
    ],
    'superAdmin' => [1, 999, 1000],
]);
return $data;
 */