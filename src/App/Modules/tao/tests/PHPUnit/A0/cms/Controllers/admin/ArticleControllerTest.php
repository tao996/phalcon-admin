<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class ArticleControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.article')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['文章']);


        $http->get('/api/m/tao.cms/admin.article')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.article/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['文章']);

        return $http->post('/api/m/tao.cms/admin.article/add', [
            'cate_id' => 1,
            'cover' => 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg',
            'title' => 'MY test article' . time(),
            'keywords' => 'test',
            'summary' => 'this is a test article',
            'author' => 'admin',
            'hits' => 150,
            'content' => '<div>Hello world</div>',
            'image_ids' => ''
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testPreview($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.article/preview?id=' . $record['id'])
            ->login()->send()->notContainsFailed()->contains($record['title']);
        return $record;
    }

    #[Depends('testPreview')] public function testCstatus($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.article/cstatus', [
            'id' => $record['id'],
            'cstatus' => 100,
            'cmessage' => '',
        ])->login()->send()->testModelSaveResponse();
        return $record;
    }

    #[Depends('testCstatus')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.article/edit?id=' . $record['id'];
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
        $http->post('/api/m/tao.cms/admin.article/delete', [
            'id' => $record['id']
        ])->login()->send()->testResponseCode0();
    }

}