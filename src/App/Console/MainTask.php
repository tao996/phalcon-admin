<?php

namespace App\Console;

use Phalcon\Cli\Task;

/**
 * 示例任务
 */
class MainTask extends Task
{
    /**
     * php artisan main
     * @return void
     */
    public function indexAction(): void
    {
        echo 'main.index.success', PHP_EOL;
    }

    /**
     * 命令: php artisan main/test
     * @return void
     */
    public function testAction(): void
    {
        echo 'main.test.000000', PHP_EOL;
    }

    /**
     * 命令：php artisan main/demo 996
     * @param int $count
     * @return void
     */
    public function demoAction(int $count = 0): void
    {
        echo 'main.demo(' . $count . ')', PHP_EOL;
    }
}