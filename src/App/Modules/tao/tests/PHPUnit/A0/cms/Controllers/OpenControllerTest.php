<?php

namespace App\Modules\tao\tests\PHPUnit\A0\cms\Controllers;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class OpenControllerTest extends TestCase
{
    public function testPage()
    {
        $http = new MyTestTaoHttpHelper($this);
        $http->get('/m/tao.cms/open/page/terms')
            ->notContainsFailed();
    }
}