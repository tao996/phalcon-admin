<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.page')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['单页']);


        $http->get('/api/m/tao.cms/admin.page')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.page/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['单页']);

        return $http->post('/api/m/tao.cms/admin.page/add', [
            'title' => '测试单页',
            'content' => 'hello world',
            'tag' => 'test',
            'name' => 'test.' . time(),
            'sort' => 0
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.page/edit?id=' . $record['id'];
        $http->get($path)
            ->login()
            ->send()->notContainsFailed()->contains('value="' . $record['title'] . '"');

        $record['content'] = 'GGG'; // 注意：content 没有追加出来

        $response = $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
        $this->assertTrue($response['content_id'] > 0);
        return $response;
    }

    #[Depends('testEdit')] public function testDelete($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.page/delete', [
            'id' => $record['id']
        ])->login()->send()->testResponseCode0();
    }

}