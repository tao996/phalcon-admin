<?php

namespace Tests\Unit\tao996\phax\Bridge;


use Phax\Bridge\Utils\PhalconData;
use PHPUnit\Framework\TestCase;


class PhalconDataTest extends TestCase
{
    public function testSessionData()
    {
        $data = PhalconData::sessionUnserialize('s:0:"";');
        $this->assertEmpty($data);
        $data = [
            'username' => 'John Doe',
            'age' => 15,
            'scores' => [
                'cn' => 100,
                'en' => 80,
                'abc' => 1,
                'cd' => '2;3;4'
            ]
        ];
        $expect = 's:110:"username|s:8:"John Doe";age|i:15;scores|a:4:{s:2:"cn";i:100;s:2:"en";i:80;s:3:"abc";i:1;s:2:"cd";s:5:"2;3;4";}";';
        $rst = PhalconData::sessionSerialize($data);
        $this->assertEquals($expect, $rst);


//        $arrRst = sessionDataToPhalconUnserialize($expect);
//        $this->assertEquals($data, $arrRst);

        $text = 's:16:"value|s:3:"100";";';
        $arrRst = PhalconData::sessionUnserialize($text);
        $expect = ['value' => 100];
        $this->assertEquals($expect, $arrRst);
    }
}