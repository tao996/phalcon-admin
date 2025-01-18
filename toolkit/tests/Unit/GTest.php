<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace Unit;

use PHPUnit\Framework\TestCase;

class GTest extends TestCase
{
    public function testRead()
    {
        $argv = ['--key=value','-name=pha'];
        $g = new \G($argv);
        $this->assertEquals('value', $g->argsOptions['key']);
        $this->assertEquals('pha', $g->argsOptions['name']);
    }
}