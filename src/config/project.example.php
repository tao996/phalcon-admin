<?php

/**
 * only set demo=>true, test.open=>true on local develop and unit test
 *
 */
return [
    // 当前是否为演示系统，管理员账号为 admin 密码 123456
    // 如果为 true，则配置文件优先级 config.demo.php > config.php
    //
    'demo' => false,
    // 是否开启测试环境，会跳过图片验证码；相关方法查看下面的文件
    /**
     * 超级管理员用户 ID 列表，不受权限控制；
     * 注意：写在前面的 user_id 可以修改写在后面的 user_id 的记录；
     * 比如 [1,2] 同样的超级管理员；但 1 可以修改 2 的记录，2 不能修改 1 的记录；
     * 如果是 [2,1] 是 2 可以修改 1 的记录，1 不能修改 2 的记录
     */
    'superAdmin' => [1, 999, 1000], // 超级管理员账号 ID, 999,1000 for phpunit test

    'test' => [
        'open' => false, // true 开启测试环境，并让上面 superAdmin 的 999, 1000 生效
        // src/App/Modules/tao/tests/Helper/MyTestTaoHttpHelper.php
        // src/tests/Helper/MyTestHttpHelper.php.pathTest($path) —— 在测试 url 后添加 '?test=on'
        // src/App/Modules/tao/Helper/Auth/LoginTestAuthAdapter.php —— 测试登录
        // src/App/Modules/tao/Helper/CaptchaHelper.php —— 验证码
        'tokens' => [
            'abc' => 1, // 'tokenValue' => userId
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
    'default' => '', // array_keys($this->sites)
];