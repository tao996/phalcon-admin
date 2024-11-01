<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class MenuControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.menu')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['添加', '添加下级']);

        $http->get('/api/m/tao/admin.menu')
            ->login()->send()
            ->notContainsFailed()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.menu/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['上级菜单']);

        $postData = [
            'pid' => 0,
            'title' => '我的测试',
            'roles' => 'superAdmin',
            'href' => 'tao/a/b/c/d',
            'type' => '2', // 模块
            'icon' => 'fa fa-list',
            'sort' => 15,
        ];
        $response = $http->post('/api/m/tao/admin.menu/add', $postData)
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
        $editPath = '/m/tao/admin.menu/edit?id=' . $id;

        $http = new MyTestTaoHttpHelper($this);
        $http->get($editPath)
            ->login()->send()->notContainsFailed()
            ->contains([
                'value="' . $data['roles'] . '"',
                'value="' . $data['title'] . '"'
            ]);

        $newTitle = 'test' . time();
        $postData = array_merge($data, ['title' => $newTitle]);

        $response = $http->post('/api' . $editPath, $postData)
            ->login()->send()->jsonResponse();

        $this->assertEquals($newTitle, $response['data']['title']);
        return $data;
    }


    #[Depends('testEdit')] public function testDelete(array $data)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/admin.menu/delete', [
            'id' => $data['id'],
        ])->login()->send()->testResponseCode0();
    }
}