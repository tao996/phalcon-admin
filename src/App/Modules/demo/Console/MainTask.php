<?php

namespace App\Modules\demo\Console;

class MainTask
{
    /**
     * php artisan m/demo/main
     */
    public function indexAction()
    {
        echo 'HELLO Phalcon admin', PHP_EOL;
    }

    /**
     * php artisan m/demo/main/test
     */
    public function testAction()
    {
        echo 'test Action', PHP_EOL;
    }

    /**
     * php artisan m/demo/main/say 15
     */
    public function sayAction(string $name)
    {
        echo 'HELLO ', $name, PHP_EOL;
    }
}