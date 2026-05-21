<?php

namespace Tests\Unit\tao996\phax\Bridge;

use Phax\Bridge\HttpDi;
use PHPUnit\Framework\TestCase;

class HttpDiTest extends TestCase
{
    public function testNotSame()
    {
        $rDi = new HttpDi();
        $rDi->setShared('hello', function () {
            return 'admin';
        });

        $this->assertEquals('admin',$rDi->get('hello'));

        $di = new \Phalcon\Di\Di();
        $this->assertFalse($di->has('hello'));
    }
}