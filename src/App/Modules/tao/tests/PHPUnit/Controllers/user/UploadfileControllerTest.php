<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers\user;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class UploadfileControllerTest extends TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.uploadfile')
            ->login()->send()
            ->notContainsFailed()
            ->contains(['文件管理']);


        $http->get('/api/m/tao/user.uploadfile')
            ->login()->send()
            ->testJsonPaginationResponse();
    }

    public function testAdd()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao/user.uploadfile/add')
            ->login()->send()->notContainsFailed()->contains(['文件地址']);
    }


}