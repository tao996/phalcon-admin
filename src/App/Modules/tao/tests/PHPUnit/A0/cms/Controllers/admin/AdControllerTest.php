<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class AdControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.ad')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['广告']);


        $http->get('/api/m/tao.cms/admin.ad')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/admin.ad/add')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['广告']);

        return $http->post('/api/m/tao.cms/admin.ad/add', [
            'begin_at' => '',
            'end_at' => '',
            'gname' => 'test',
            'title' => 'just a test ad',
            'tag' => '直播',
            'sort' => 0,
            'cover' => 'http://assets.emm365.com//b408d7ed07687c13dfa8d11fe789380f.jpg',
            'at_banner' => 'on',
            'at_index' => 'on',
            'link' => '/m/tao/link/index',
            'kind' => 1,
            'remark' => 'just a test'
        ])->login()->send()->testModelSaveResponse();
    }

    #[Depends('testAdd')] public function testEdit($record)
    {
        $http = new MyTestTaoHttpHelper($this);
        $path = '/m/tao.cms/admin.ad/edit?id=' . $record['id'];
        $http->get($path)
            ->login()
            ->send()->notContainsFailed()->contains('value="' . $record['title'] . '"');

        return $http->post('/api' . $path, $record)
            ->login()->send()->testModelSaveResponse();
    }

    #[Depends('testEdit')] public function testDelete($page)
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao.cms/admin.ad/delete', [
            'id' => $page['id']
        ])->login()->send()->testResponseCode0();
    }

}