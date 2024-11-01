<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.user')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['用户管理']);

        $http->get('/api/m/tao/admin.user')
            ->login()->send()
            ->notContainsFailed()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.user/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['用户头像', '确认', '重置']);

        $postData = [
            'nickname' => 'test-' . time(),
            'password' => '123456',
            'role_ids[2]' => 'on',
        ];
        $data = $http->post('/api/m/tao/admin.user/add', $postData)
            ->login()->send()->notContainsFailed()->jsonResponse();
        $this->assertStringContainsString('必须', $data['msg']); // 必须设置一个登录账号

        $postData['email'] = time() . '@test.com';
        $postData['email_valid'] = 'on';
        $data = $http->post('/api/m/tao/admin.user/add', $postData)
            ->login()->send()->notContainsFailed()->jsonResponse();

        $id = $data['data']['id'];
        $this->assertTrue($id > 0); // 添加成功

        return $data['data'];
    }

    #[Depends('testAdd')] public function testEdit(array $data)
    {
        $id = $data['id'];
        $this->assertTrue($id > 0); // 添加成功
        // 编辑
        $editPath = '/m/tao/admin.user/edit?id=' . $id;


        $http = new MyTestTaoHttpHelper($this);
        $http->get($editPath)
            ->login()->send()->notContainsFailed()
            ->contains([
                'value="' . $data['nickname'] . '"',
                'value="' . $data['email'] . '"'
            ]);

        $postData = [
            'nickname' => $data['nickname'],
            'signature' => 'mysign.' . time()
        ];

        $response = $http->post('/api' . $editPath, $postData)
            ->login()->send()->jsonResponse();
        $this->assertEquals($postData['signature'], $response['data']['signature']);
        return $data;
    }

    #[Depends('testEdit')] public function testPassword(array $data)
    {
        $id = $data['id'];
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.user/password?id=' . $id)
            ->login()->send()->notContainsFailed()
            ->contains(['旧的密码', '新的密码']);

        $response = $http->post('/api/m/tao/admin.user/password?id=' . $id, [
            'old_password' => '123456',
            'password' => '123456'
        ])->login()->send()->jsonResponse();
        $this->assertEquals(0, $response['code']);
    }

    #[Depends('testEdit')] public function testDelete(array $data)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/admin.user/delete', [
            'id' => $data['id'],
        ])->login()->send()->testResponseCode0();
    }
}