<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers;

use PHPUnit\Framework\TestCase;
use Tests\Helper\MyTestHttpHelper;

class AuthControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/tao/auth/index')
            ->send()
            ->notContainsFailed()
            ->contains(['后台系统', '注册帐号', '忘记密码', '验证码登录']);

        $data = $http->post('/api/m/tao/auth/index', [
            'account' => 'admin',
            'password' => '123456',
            'captcha' => '1234'
        ])->cookie()->send()->jsonResponse();
        // token
        $this->assertTrue(str_starts_with($data['data'], '1:web'));
    }

    public function testSignup()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/tao/auth/signup')
            ->send()
            ->notContainsFailed()
            ->contains(['账号注册', '前往登录']);
    }

    public function testForgot()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/tao/auth/forgot')
            ->send()
            ->notContainsFailed()
            ->contains(['忘记密码', '已有账号', '没有账号']);

        $data = $http->post('/api/m/tao/auth/forgot', [
            'account' => 'admin@abc.com',
            'captcha' => '1234'
        ])->send()->jsonResponse();
        $this->assertStringContainsString('没有找到', $data['msg']);

        $data = $http->post('/api/m/tao/auth/forgot', [
            'account' => 'admin@test.com',
            'captcha' => '1234'
        ])->send()->jsonResponse();

        $this->assertStringContainsString('重置密码邮件已发送',$data['msg']);
    }

    public function testSignin()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/tao/auth/signin')
            ->send()
            ->notContainsFailed()
            ->contains(['验证码登录']);
    }
}