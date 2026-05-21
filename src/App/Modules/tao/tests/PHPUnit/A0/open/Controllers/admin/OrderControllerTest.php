<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;

class OrderControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndex()
    {
        $http = new MyTestTaoHttpHelper($this);

        $http->get('/m/tao.open/admin.order')
            ->login()->send()->notContainsFailed()->contains(['订单管理']);

        $http->get('/api/m/tao.open/admin.order')
            ->login()->send()->testJsonPaginationResponse();
    }
}