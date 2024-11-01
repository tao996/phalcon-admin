<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;

class MchControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);

        $http->get('/m/tao.open/admin.mch')
            ->login()->send()->notContainsFailed()->contains(['商户']);

        $http->get('/api/m/tao.open/admin.mch')
            ->login()->send()->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.open/admin.mch/add')
            ->login()->send()->notContainsFailed();

        $postData = [
            'appid' => 'wx'.time(),
            'mchid' => 'mch'.time(),
            'secret_key' => '123456789123456789123456789',
            'v2_secret_key' => '123456789123456789123456789',
            'remark' => 'just a test'
        ];
        return $http->post('/api/m/tao.open/admin.mch/add', $postData)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $path = '/m/tao.open/admin.mch/edit?id=' . $record['id'];
        $http = new MyTestTaoHttpHelper($this);
        $http->get($path)->login()
            ->send()->notContainsFailed()->contains(['value="' . $record['secret_key'] . '"']);

        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.open/admin.mch/delete', ['id' => $record['id']])
            ->login()->send()->testResponseCode0();
    }
}