<?php

declare(strict_types=1);
namespace Tests\Unit\tao996\phax\Facade;

class MyHelperFacadeTest extends \PHPUnit\Framework\TestCase
{
    public function testFunctions()
    {
        $helper = new \Phalcon\Support\HelperFactory();

        $this->assertEquals('CameLiZe', $helper->camelize('came_li_ze'));
        $this->assertEquals('CameLiZe', $helper->camelize('came-li-ze', '-'));
        $this->assertEquals('cameLiZe', $helper->camelize('came-li-ze', '-', true));

        $this->assertEquals('came_li_ze', $helper->uncamelize('CameLiZe'));
        $this->assertEquals('came-li-ze', $helper->uncamelize('CameLiZe', '-'));

        // 帕斯卡命名法（首字母大写）
        $this->assertEquals('CustomerSession', $helper->pascalCase('customer-session'));
        $this->assertEquals('CustomerSession', $helper->pascalCase('customer_session', '_'));
    }
}