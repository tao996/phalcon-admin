<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use Phax\Utils\MyData;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class QuickControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.quick')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['链接']);


        $response = $http->get('/api/m/tao/user.quick')
            ->login()->send()
            ->jsonResponse();
        MyData::mustHasSet($response['data'], ['count', 'rows']);
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.quick/add')
            ->login()->send()->notContainsFailed()->contains(['菜单名称']);

        $response = $http->post('/api/m/tao/user.quick/add', [
            'title' => '测试快捷链接',
            'href' => '/m/tao/user.quick/add',
            'remark' => '测试快捷链接',
            'icon' => '',
            'sort' => 1,
        ])->login()->send()->jsonResponse();

        $id = $response['data']['id'];
        $this->assertTrue($id > 0);

        return $id;
    }

    #[Depends('testAdd')] public function testEdit($id)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.quick/edit?id=' . $id)
            ->login()->send()->notContainsFailed()->contains(['菜单名称']);

        $remark = 'time.' . time();
        $response = $http->post('/api/m/tao/user.quick/edit?id=' . $id, [
            'title' => '测试快捷链接',
            'href' => '/m/tao/user.quick/edit',
            'remark' => $remark
        ])->login()->send()->jsonResponse();
        $this->assertEquals($remark, $response['data']['remark']);

        return $id;
    }

    #[Depends('testEdit')] public function testDelete($id)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/user.quick/delete', [
            'id' => $id,
        ])->login()->send()->testResponseCode0();
    }


}