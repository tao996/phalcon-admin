<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class FileControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.file')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['文件选择']);

        $http->get('/api/m/tao/user.file')
            ->login()->send()->testJsonPaginationResponse();
    }

}