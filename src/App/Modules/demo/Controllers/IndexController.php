<?php

namespace App\Modules\demo\Controllers;

use Phax\Mvc\Controller;

class IndexController extends Controller
{
    /**
     * 模型关联
     * @link http://localhost:8071/api/m/demo/index/index
     * @return array
     */
    public function indexAction(): array
    {
        return [];
    }

    /**
     * @link http://localhost:8071/api/m/demo/index/hello
     * @param string $name
     * @return array|string[]
     */
    public function helloAction(string $name = 'phalcon'): array
    {
        return ['name' => $name];
    }
}