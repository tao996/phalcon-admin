<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class NodeControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.node')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['节点']);

        $http->get('/api/m/tao/admin.node')
            ->login()->send()
            ->notContainsFailed()
            ->testJsonPaginationResponse();
    }

    public function testModify()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/admin.node/modify', [
            'id' => 1,
            'field' => 'title',
            'value' => '系统管理模块'
        ])->login()->send()->testResponseCode0();
    }

    public function testReload()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/api/m/tao/admin.node/reload')
            ->login()
            ->send()
            ->notContainsFailed()->testResponseCode0();

        $http->get('/api/m/tao/admin.node/reload/true')
            ->login()
            ->send()
            ->notContainsFailed()->testResponseCode0();
    }

}