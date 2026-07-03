<?php

namespace App\Modules\demo\Controllers;

use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BusinessException;


#[RBAC(title: 'demo.表单')]
class TodoController extends Controller
{
    /**
     * @link http://localhost:8071/api/m/demo/todo/list
     */
    #[RBAC(title: 'list1')]
    public function listAction(): array
    {
        return ['name' => 'todo list'];
    }

    /**
     * @link http://localhost:8071/api/m/demo/todo/test1
     * @return mixed
     */
    public function test1Action(): mixed
    {
        throw new BusinessException('没有 rbac 标记，不会读取');
    }

    /**
     * @link http://localhost:8071/api/m/demo/todo/test2
     * @return mixed
     */
    #[RBAC(title: 'test2', close: 1)]
    public function test2Action(): mixed
    {
        throw new BusinessException('使用 {close:1}，不会读取');
    }
}