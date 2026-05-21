<?php

namespace Tests\Unit\app\Http;

use Tests\Helper\MyTestHttpHelper;

class ControllersTest extends \PHPUnit\Framework\TestCase
{
    public function testIndex()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/')->send()
            ->notContainsFailed()
            ->contains(['welcome to phalcon admin project!', $http->getOrigin() . '/index/about']);


        $http->get('/index/about')->send()
            ->notContainsFailed()
            ->contains(['接收参数:', 'name:Phalcon', 'age: 0']);

        $http->get('/index/about/phax/5')->send()
            ->notContainsFailed()
            ->contains(['接收参数:', 'name:phax', 'age: 5']);
    }

    public function testSubTest()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/sub.test/abc')->send()
            ->notContainsFailed()
            ->contains(['子目录', '<span>data: <span>ABC</span></span>']);

        $http->get('/sub/sub1.me/say')->send()
            ->notContainsFailed()
            ->contains(['<span>name: <span>ME~~~~']);
    }
}