<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;

class AppControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);

        $http->get('/m/tao.open/admin.app')
            ->login()->send()->notContainsFailed()->contains(['应用']);

        $http->get('/api/m/tao.open/admin.app')
            ->login()->send()->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.open/admin.app/add')
            ->login()->send()->notContainsFailed()->contains(['应用名称']);

        $postData = [
            'title' => 'myTest:' . time(),
            'platform' => 1,
            'kind' => 'mini',
            'appid' => 'wx' . time(),
            'secret' => '456789',
            'crop_id' => 'crop123',
            'token' => 123,
            'enc_method' => 'aes',
            'aes_key' => '123456',
            'sandbox' => 'on',
            'remark' => 'ad test'
        ];
        return $http->post('/api/m/tao.open/admin.app/add', $postData)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $path = '/m/tao.open/admin.app/edit?id=' . $record['id'];
        $http = new MyTestTaoHttpHelper($this);
        $http->get($path)->login()
            ->send()->notContainsFailed()->contains(['value="' . $record['aes_key'] . '"']);

        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.open/admin.app/delete', ['id' => $record['id']])
            ->login()->send()->testResponseCode0();
    }
}