<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class CategoryControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.category')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['栏目']);


        $http->get('/api/m/tao.cms/admin.category')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.category/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['栏目']);

        return $http->post('/api/m/tao.cms/admin.category/add', [
            'pid' => 0,
            'kind' => 1,
            'title' => 'MyTest',
            'name' => 'mytest',
            'tag' => 'test',
            'cover' => 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg',
            'summary' => 'this is a test article',
            'content' => '<div>category test content</div>',
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.category/edit?id=' . $record['id'];
        $http->get($path)
            ->login()
            ->send()->notContainsFailed()->contains('value="' . $record['title'] . '"');

        $record['content'] =time();
        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($page)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.category/delete', [
            'id' => $page['id']
        ])->login()->send()->testResponseCode0();
    }

}