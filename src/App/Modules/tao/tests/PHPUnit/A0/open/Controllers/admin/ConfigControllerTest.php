<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;

class ConfigControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);

        $http->get('/m/tao.open/admin.config')
            ->login()->send()->notContainsFailed();
    }

}