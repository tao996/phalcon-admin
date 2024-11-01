<?php

namespace App\Modules\demo\Controllers;

use Phax\Mvc\Controller;

/**
 * @rbac ({title:'表单'})
 */
class TodoController extends Controller
{
    /**
     * @rbac ({title:'list1'})
     */
    public function listAction(): array
    {
        return ['name' => 'todo list'];
    }

    public function test1Action()
    {
        throw new \Exception('没有 rbac 标记，不会读取');
    }

    /**
     * @rbac ({title:'test2',close:1})
     */
    public function test2Action()
    {
        throw new \Exception('使用 {close:1}，不会读取');
    }
}