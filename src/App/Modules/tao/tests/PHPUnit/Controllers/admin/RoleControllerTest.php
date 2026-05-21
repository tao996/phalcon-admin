<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class RoleControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.role')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['添加']);

        $http->get('/api/m/tao/admin.role')
            ->login()->send()
            ->notContainsFailed()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.role/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['角色名称']);

        $postData = [
            'title' => '测试.' . time(),
            'name' => 'test' . time(),
            'remark' => 'this is a test',
        ];
        $response = $http->post('/api/m/tao/admin.role/add', $postData)
            ->login()->send()->notContainsFailed()->jsonResponse();

        $id = $response['data']['id'];
        $this->assertTrue($id > 0); // 添加成功

        return $response['data'];
    }


    #[Depends('testAdd')] public function testEdit(array $data)
    {
        $id = $data['id'];
        $this->assertTrue($id > 0); // 添加成功
        // 编辑
        $editPath = '/m/tao/admin.role/edit?id=' . $id;

        $http = new MyTestTaoHttpHelper($this);
        $http->get($editPath)
            ->login()->send()->notContainsFailed()
            ->contains([
                'value="' . $data['title'] . '"',
                'value="' . $data['name'] . '"'
            ]);

        $postData = [
            'title' => $data['title'],
            'name' => $data['name'],
            'remark' => '123'
        ];

        $response = $http->post('/api' . $editPath, $postData)
            ->login()->send()->jsonResponse();

        $this->assertEquals($postData['remark'], $response['data']['remark']);
        return $data;
    }

    #[Depends('testEdit')] public function testAuthorized(array $data)
    {
        $id = $data['id'];
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.role/authorize?id=' . $id)
            ->login()->send()->notContainsFailed()->contains([
                'value="' . $data['title'] . '"'
            ]);

        $response = $http->get('/api/m/tao/admin.role/authorize?id=' . $id)
            ->login()->send()->jsonResponse();
        $this->assertTrue(count($response['data']) > 0);

        $http->post('/api/m/tao/admin.role/authorize?id=' . $id, [
            "title" => '测试',
            'node' => '2,3,6,7'
        ])->login()->send()->testResponseCode0();
        return $data;
    }

    #[Depends('testAuthorized')] public function testDelete(array $data)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/admin.role/delete', [
            'id' => $data['id'],
        ])->login()->send()->testResponseCode0();
    }
}