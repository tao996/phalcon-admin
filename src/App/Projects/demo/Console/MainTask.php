<?php

namespace App\Projects\demo\Console;

class MainTask
{
    /**
     * php artisan p/demo/main
     */
    public function indexAction()
    {
        echo 'Project demo', PHP_EOL;
    }

    /**
     * php artisan p/demo/main/test
     */
    public function testAction()
    {
        echo 'Project demo test Action', PHP_EOL;
    }

    /**
     * php artisan p/demo/main/say 15
     */
    public function sayAction(string $name)
    {
        echo 'Project demo say Action: ', $name, PHP_EOL;
    }
}