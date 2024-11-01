<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class LinkControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.link')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['链接']);


        $http->get('/api/m/tao.cms/admin.link')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.link/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['链接']);

        return $http->post('/api/m/tao.cms/admin.link/add', [
            'tag' => 1,
            'sort' => 15,
            'title' => '百度',
            'href' => 'http://www.baidu.com'
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.link/edit?id=' . $record['id'];
        $http->get($path)
            ->login()
            ->send()->notContainsFailed()->contains('value="' . $record['title'] . '"');

        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($page)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.link/delete', [
            'id' => $page['id']
        ])->login()->send()->testResponseCode0();
    }

}