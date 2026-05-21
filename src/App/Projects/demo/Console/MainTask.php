<?php

namespace App\Projects\demo\Console;


use Phax\Mvc\Console;

class MainTask extends Console
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
        echo 'HELLO ' . $name;
    }
}