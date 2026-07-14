<?php

namespace Tests\Unit\app\Http;

use Tests\Helper\MyTestHttpHelper;

class ControllersTest extends \PHPUnit\Framework\TestCase
{

    public function testSubTest()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/sub.test/abc')->send()
            ->notContainsFailed()
            ->contains(['404']);
    }
}