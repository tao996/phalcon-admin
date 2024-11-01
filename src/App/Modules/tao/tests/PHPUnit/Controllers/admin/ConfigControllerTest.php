<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class ConfigControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/admin.config')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['网站设置']);


        $http->get('/api/m/tao/admin.config')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testSave()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->post('/api/m/tao/admin.config/save/html', [
            'header' => '',
            'footer' => 'HELLO'
        ])->login()->send()->testResponseCode0();
    }

}