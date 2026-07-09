<?php

namespace App\Modules\demo\tests;

use Phax\Foundation\AppService;
use Tests\Helper\MyTestCaseHelper;

class DemoTest extends MyTestCaseHelper
{
    public function testDb()
    {
        $mysql = AppService::config()->getArray('database.stores.mysql');
        $this->assertNotEmpty($mysql['dbname']);
    }
}