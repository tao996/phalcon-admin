<?php

namespace App\Modules\demo\tests;

use Tests\Helper\MyTestCaseHelper;

class DemoTest extends MyTestCaseHelper
{
    public function testDb()
    {
        $mvc = $this->getMyMvc();
        $mysql = $mvc->config()->path('database.stores.mysql')->toArray();
        $this->assertNotEmpty($mysql['dbname']);
    }
}