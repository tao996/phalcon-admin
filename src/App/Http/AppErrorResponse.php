<?php

namespace App\Http;

use Phax\Foundation\AppService;
use Phax\Mvc\Controller;

/**
 * 注意：这个类不是正经初始化的
 */
class AppErrorResponse extends Controller
{


    /**
     * 异常时响应
     * API → JSON，Web → 错误页面
     * @param \Throwable $e
     */
    public function exception(\Throwable $e): string
    {
        // 日志已由 handleException() 统一处理，此处只负责渲染响应
        if ($this->isApiRequest()) {
            $code = $e->getCode() ?: 500;
            $msg = IS_DEBUG ? $e->getMessage() : '系统繁忙，请稍后再试';
            $data = IS_DEBUG ? [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ] : null;
            AppService::echoJsonData([
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
            ]);
        }

        // Web：渲染错误页面
        $this->view->setViewsDir(__DIR__ . '/../Http/views/');
        $this->view->setMainView('');
        $this->view->pick('error/exception');
        $this->view->e = $e;
        $this->view->start();
        $this->view->render('error', 'exception');
        $this->view->finish();
        return $this->view->getContent();
    }

    /**
     * 路由没有匹配到（404）
     * @param \Throwable $e
     */
    public function notFound(\Throwable $e): string
    {
        if ($this->isApiRequest()) {
            $data = IS_DEBUG ? [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'message' => $e->getMessage(),
            ] : null;
            AppService::echoJsonData([
                'code' => 404,
                'msg' => '接口不存在',
                'data' => $data,
            ]);
        }

        $this->view->setViewsDir(__DIR__ . '/../Http/views/');
        $this->view->setMainView('');
        $this->view->pick('error/not_found');
        $this->view->e = $e;
        $this->view->start();
        $this->view->render('error', 'not_found');
        $this->view->finish();
        return $this->view->getContent();
    }
}
