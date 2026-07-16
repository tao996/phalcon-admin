<?php

$data = include __DIR__ . '/services.docker.example.php';
$data['crypt']['key'] = '123456'; // 只能修改一次，否则加密的账号信息解密失败
// only for dev
$data['metadata']['driver'] = 'memory';
$data['database']['log']['driver'] = 'file';

$data['app'] = [
    'title' => 'Phalcon Admin Dev', // 应用标题/名称
    'origin' => 'http://localhost:8071/', // 必须以 / 结尾，用于生成链接地址；默认从 $_SERVER['HTTP_HOST'] 或其它参数中获取
    'logo' => '/assets/logo.png', // 30*30
    'timezone' => env('TZ', 'UTC'),
    'locale' => 'cn', // 默认的语言（总是2位）[a-z]{2}
    // 暂时还未启用
//    'jwt' => [
//        'hmac' => 'sha256',
//        'secret' => 'phalcon', // must modify
//        'expire' => 3600 * 48, // 建议修改
//        'subject' => 'jwt'
//    ],
    // 当访问路径为 '' 或 '/' 时，默认访问的链接
    'default' => '',
    'defaultApp' => [
        // 自定义时只能指向 App\Modules\xxx\Controller 或者 App\Projects\xxx\Controller
//        'namespace' => 'App\Http\Controllers',
        // viewpath 默认会根据 namespace 进行判断
//        'viewpath' => PATH_APP . 'Http' . DIRECTORY_SEPARATOR . 'views',
    ],
    // 异常和错误处理的类
    'error' => 'App\Http\AppErrorResponse', // 默认值
    // 后台首页，必须以 / 开头
    // 'welcome'=>'/m/tao/index/welcome',
    'assets' => [
        // cn|ncn|(your cdn domain); 本地开发时，
        // 默认为空（即 self）：views/assets 中读取本地资源,通常用在开发阶段，不需要外网加载资源
        // cn: 默认为 https://cdn.staticfile.org/
        // ncn: 默认为 https://cdnjs.cloudflare.com/ajax/libs/
        // 其它链接地址：cdn 地址
        'cdn' => '',
        // 用户允许上传/使用的图片域名列表
        'hosts' => [],
        // 本地 css/js 是否使用压缩文件
        'min' => false,
    ],
    // 配置自动加载的类和文件
    'loader' => [
        'namespaces' => [], // loader()->setNamespaces
        'includes' => [], // include_once files list
    ],
    // 演示系统配置
    'demo' => [
        /*
         * 是否开启演示系统，生产环境下必须设置为 false
         * 1。 开启后会跳过图形验证码验证（总是通过）
         */
        'open' => true,
        // 演示系统管理员账号为 admin 密码 123456
        'admin' => [
            'account' => 'admin',
            'password' => '123456'
        ],
    ],
    /**
     * 超级管理员用户 ID 列表，不受权限控制；
     * 注意：写在前面的 user_id 可以修改写在后面的 user_id 的记录；
     * 比如 [1,2] 同样的超级管理员；但 1 可以修改 2 的记录，2 不能修改 1 的记录；
     * 如果是 [2,1] 是 2 可以修改 1 的记录，1 不能修改 2 的记录
     * 在生产环境下，必须设置为 [] 或者 [1]，只保留1个的超级管理员
     */
    'superAdmin' => [1, 999, 1000], // 超级管理员账号 ID, 999,1000 for phpunit test

    // 测试环境配置
    'test' => [
        // 在生产环境下，必须设置为 false
        // 开启测试环境，会跳过图片验证码；
        'open' => true,
        /*
         * 流程
         * 格式，token=>userId
         src/App/Modules/tao/tests/Helper/MyTestTaoHttpHelper.php
         src/tests/Helper/MyTestHttpHelper.php.pathTest($path) —— 在测试 url 后添加 '?test=on'
         src/App/Modules/tao/Helper/Auth/LoginTestAuthAdapter.php —— 测试登录
         src/App/Modules/tao/Helper/CaptchaHelper.php —— 验证码
         */
        'tokens' => [
            'tao' => 1,
            'house' => 1000,
        ],
    ],
    /*
     IP 白名单，支持三种格式：
       '192.168.1.1'       — 精确匹配
       '192.168.1.0/24'    — CIDR 网段
       '192.168.*'         — 通配符
     留空数组 [] 表示不限制
     */
    'ipWhitelist' => [],
];


return $data;