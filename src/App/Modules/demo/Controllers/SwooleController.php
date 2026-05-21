<?php

namespace App\Modules\demo\Controllers;


use Phax\Mvc\Controller;

class SwooleController extends Controller
{
    /**
     * http://localhost:8071/m/demo/swoole/session
     */
    public function sessionAction()
    {
        if ($this->request->hasQuery('data')) {
            $data = $this->request->getQuery('data');
            $this->session->set('time', $data);
            // 存在问题，session 不会马上写入到 redis 中
        }

        return [
            'id' => $this->session->getId(),
        ];
    }

    public function cookieAction()
    {
    }
}