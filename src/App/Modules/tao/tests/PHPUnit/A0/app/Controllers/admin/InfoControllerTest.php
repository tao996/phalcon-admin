<?php

namespace App\Modules\tao\tests\PHPUnit\A0\app\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class InfoControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.app/admin.info')
            ->login()->send()->notContainsFailed();

        $http->get('/api/m/tao.app/admin.info')
            ->login()->send()->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.app/admin.info/add')
            ->login()->send()->notContainsFailed()
            ->contains(['应用信息']);

        return $http->post('api/m/tao.app/admin.info/add', [
            'title' => 'Test Title',
            'tag' => 'test',
            'remark' => 'Just a test app'
        ])->login()->send()->testModelSaveResponse();

    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.app/admin.info/edit?id=' . $record['id'];
        $http->get($path)
            ->login()->send()->notContainsFailed()
            ->contains(['value="' . $record['title'] . '"']);

        $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();

        return $record;
    }

    #[Depends('testEdit')] public function testDelete($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.app/admin.info/delete', ['id' => $record['id']])
            ->login()->send()->testResponseCode0();
    }
}