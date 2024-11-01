<?php

namespace Tests\Unit\app\Projects\demo\Console;

class MainTaskTest extends \PHPUnit\Framework\TestCase
{

    public function testTask()
    {
        $rst = \Tests\Helper\MyTestTaskHelper::cmd('p/demo/main');
        $this->assertEquals('Project demo', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('p/demo/main/test');
        $this->assertEquals('Project demo test Action', $rst);

        $rst = \Tests\Helper\MyTestTaskHelper::cmd('p/demo/main/say 15');
        $this->assertEquals('Project demo say Action: 15', $rst);
    }
}