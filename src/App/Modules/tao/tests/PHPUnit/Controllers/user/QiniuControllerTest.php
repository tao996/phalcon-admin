<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use Phax\Utils\MyData;
use PHPUnit\Framework\TestCase;

class QiniuControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $response = $http->get('/api/m/tao/user.qiniu/index')
            ->login()->send()
            ->jsonResponse();
        MyData::mustHasSet($response['data'], ['token', 'expire','domain']);
    }

}