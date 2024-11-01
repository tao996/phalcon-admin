<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class AlbumControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.album')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['图集']);


        $http->get('/api/m/tao.cms/admin.album')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.album/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['图片']);

        return $http->post('/api/m/tao.cms/admin.album/add', [
            'cover' => 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg',
            'title' => 'just a test album',
            'tag' => 'test',
            'summary' => 'this is a test',
            'image_ids' => '',// '19,18'
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.album/edit?id=' . $record['id'];
        $http->get($path)
            ->login()
            ->send()->notContainsFailed()->contains('value="' . $record['title'] . '"');

        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($page)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.album/delete', [
            'id' => $page['id']
        ])->login()->send()->testResponseCode0();
    }

}