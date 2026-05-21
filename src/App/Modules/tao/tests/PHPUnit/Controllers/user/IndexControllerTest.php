<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class IndexControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.index/index')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['会员中心']);


        $http->post('/api/m/tao/user.index/index', [
            'head_img' => '',
            'nickname' => 'admin996',
            'signature' => 'HELLO WORLD'
        ])->login()->send()->testResponseCode0();
    }

    public function testChangeEmail()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.index/change-email')
            ->login()->send()->notContainsFailed()->contains(['登录邮箱']);
    }

    public function testChangePhone()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.index/change-phone')
            ->login()->send()->notContainsFailed()->contains(['手机号']);
    }

    public function testPassword()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.index/password')
            ->login()->send()->notContainsFailed()->contains(['登录密码', '确认密码']);

        $http->post('/api/m/tao/user.index/password', [
            'password' => '123456',
            'password_confirm' => '123456'
        ])->login()->send()->testResponseCode0();
    }

}