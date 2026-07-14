<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;


class IndexControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = MyTestTaoHttpHelper::with($this);
        $http->get('/m/tao')
            ->login()->send()
            ->notContainsFailed();

        $data = $http->get('/api/m/tao')
            ->login()->send()
            ->jsonResponse();
        $this->assertNotEmpty($data['data']['menuTree']);

        $http->get('/m/tao/index/welcome')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['欢迎界面']);
    }
}