<?php

namespace App\Http;

use Phax\Mvc\Controller;


class AppErrorResponse extends Controller
{

    /**
     * 异常时响应
     * @param \Exception $e
     */
    public function exception(\Exception $e)
    {
        // 如果是 api 则返回错误信息，否是携带错误信息重新渲染视图
        if ($this->isApiRequest()) {
            $this->json([
                'code' => intval($e->getCode()) ?: 500,
                'msg' => $e->getMessage(),
                'data' => null,
            ]);
        } else {
            return "TODO Error : " . $e->getMessage();
        }
    }

    /**
     * 路由没有匹配到
     * @param \Exception $e
     * @return void
     */
    public function notFound(\Exception $e)
    {
        ddd($e->getMessage());
    }
}