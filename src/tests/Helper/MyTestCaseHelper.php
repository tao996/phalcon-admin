<?php

namespace Tests\Helper;

use Phax\Foundation\Application;
use Phax\Helper\MyMvc;
use PHPUnit\Framework\TestCase;

class MyTestCaseHelper extends TestCase
{
    protected \Phalcon\Di\Di $di;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 获取或初始化 Phalcon 的全局 DI
        // 如果你的公共框架有初始化 DI 的静态方法，直接调用它，比如：
        // \Phax\Foundation\Application::initTestDi();

        $this->di = Application::di();

        // 2. 开启数据库事务（还记得上一题聊到的高效恢复状态方案吗？）
        if ($this->di->has('db')) {
            $this->di->get('db')->begin();
        }
    }

    protected function tearDown(): void
    {
        // 3. 测试结束，直接回滚，保证不污染测试数据库
        if ($this->di && $this->di->has('db')) {
            $this->di->get('db')->rollback();
        }

        parent::tearDown();
    }

    protected function getMyMvc(): MyMvc
    {
        return new MyMvc($this->di);
    }
}