<?php
namespace Tests\Unit\app\Console;

class MainTaskTest extends \PHPUnit\Framework\TestCase
{
    public function testTask()
    {
        $rst = \Tests\Helper\MyTestTaskHelper::cmd('main');
        $this->assertEquals('main.index.success', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('main/test');
        $this->assertEquals('main.test.000000', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('main/demo 996');
        $this->assertEquals('main.demo(996)', $rst);
    }
}