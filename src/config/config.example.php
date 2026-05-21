<?php

$data = include __DIR__ . '/services.example.php';
$data['crypt']['key'] = '123456'; // 只能修改一次，否则加密的账号信息解密失败
// only for dev
$data['metadata']['driver'] = 'memory';
$data['database']['log']['driver'] = 'file';

$data['app'] = [
    'title' => 'Phalcon Admin Dev', // 应用标题/名称
    'url' => 'http://localhost:8071/', // 必须以 / 结尾，用于生成链接地址；默认从 $_SERVER['HTTP_HOST'] 或其它参数中获取
    'https' => false, // 是否将 http 转为 https，线上的时候需要设置为 true
    'logo' => '/assets/logo.png', // 30*30
    'timezone' => env('TZ', 'UTC'),
    'locale' => 'cn', // 默认的语言（总是2位）[a-z]{2}
    'jwt' => [
        'hmac' => 'sha256',
        'secret' => 'phalcon', // must modify
        'expire' => 3600 * 48, // 建议修改
        'subject' => 'jwt'
    ],
    // 异常和错误处理的类
    'error' => 'App\Http\AppErrorResponse',
    // cn|ncn|(your cdn domain); 本地开发时，可不填；应用/模块会从自动的 views/assets 中读取本地资源
    'cdn_locate' => 'cn',
    'hosts' => [], // use check images origin when user upload/select image
    // see src/tao996/Phax/Foundation/Application.php
    'loader' => [
        'namespaces' => [], // loader()->setNamespaces
        'includes' => [], // include_once files list
    ],
    // 当前是否为演示系统，管理员账号为 admin 密码 123456
    // 如果为 true，则配置文件优先级 config.demo.php > config.php
    // 在生产环境下，必须设置为 false
    'demo' => true,
    // 是否开启测试环境，会跳过图片验证码；相关方法查看下面的文件
    /**
     * 超级管理员用户 ID 列表，不受权限控制；
     * 注意：写在前面的 user_id 可以修改写在后面的 user_id 的记录；
     * 比如 [1,2] 同样的超级管理员；但 1 可以修改 2 的记录，2 不能修改 1 的记录；
     * 如果是 [2,1] 是 2 可以修改 1 的记录，1 不能修改 2 的记录
     * 在生产环境下，必须设置为 [] 或者 [1]，只保留1个的超级管理员
     */
    'superAdmin' => [1, 999, 1000], // 超级管理员账号 ID, 999,1000 for phpunit test

    // phpunit test
    'test' => [
        // 在生产环境下，必须设置为 false
        'open' => true, // true 开启测试环境，并让上面 superAdmin 的 999, 1000 生效
        // src/App/Modules/tao/tests/Helper/MyTestTaoHttpHelper.php
        // src/tests/Helper/MyTestHttpHelper.php.pathTest($path) —— 在测试 url 后添加 '?test=on'
        // src/App/Modules/tao/Helper/Auth/LoginTestAuthAdapter.php —— 测试登录
        // src/App/Modules/tao/Helper/CaptchaHelper.php —— 验证码
        'tokens' => [
            'tao' => 1, // 'tokenValue' => userId
            'house' => 1000, // just an example: token for src/Projects/house
        ],
    ],
    // '项目'=>'域名不需要添加 www.' 如 ['abc' => ['abc.test','abc.com']]
    // abc 是项目名，位于 src/App/Projects/abc
    // 可查看 src/tao996/Phax/Support/Config.php
    'sites' => [
        'demo' => ['demo1.test', 'demo2.test'],
        'aabb' => ['a1.com', 'b1.org']
    ],
    // 默认的项目
    'default' => '', // if default=xxx, then src/App/Projects/xxx will be visited default
];


return $data;