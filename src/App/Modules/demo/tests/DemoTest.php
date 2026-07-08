<?php

namespace App\Modules\demo\tests;

use Tests\Helper\MyTestCaseHelper;

class DemoTest extends MyTestCaseHelper
{
    public function testDb()
    {
        $mvc = $this->getMyMvc();
        $mysql = $mvc->config()->getArray('database.stores.mysql');
        $this->assertNotEmpty($mysql['dbname']);
    }
}