<?php

namespace Tests\Unit\app\Modules\demo\Console;

class MainTaskTest extends \PHPUnit\Framework\TestCase
{
    public function testTask()
    {
        $rst = \Tests\Helper\MyTestTaskHelper::cmd('m/demo/main');
        $this->assertEquals('HELLO Phalcon admin', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('m/demo/main/test');
        $this->assertEquals('test Action', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('m/demo/main/say 15');
        $this->assertEquals('HELLO 15', $rst);
    }
}