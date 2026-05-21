<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use Phax\Utils\MyData;
use PHPUnit\Framework\TestCase;

class LogControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.log')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['日志']);


       $http->get('/api/m/tao/user.log')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

}