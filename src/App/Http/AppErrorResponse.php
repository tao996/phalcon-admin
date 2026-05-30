<?php

namespace App\Http;

use Phax\Mvc\Controller;
use Phax\Support\Logger;


class AppErrorResponse extends Controller
{
    private function isDebug(): bool
    {
        return defined('IS_DEBUG') && IS_DEBUG;
    }

    /**
     * 异常时响应
     * API → JSON，Web → 错误页面
     * @param \Throwable $e
     */
    public function exception(\Throwable $e): string
    {
        Logger::exception($e);

        if ($this->isApiRequest()) {
            $code = intval($e->getCode()) ?: 500;
            $msg = $this->isDebug() ? $e->getMessage() : '系统繁忙，请稍后再试';
            $data = $this->isDebug() ? [
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ] : null;

            $this->json([
                'code' => $code,
                'msg' => $msg,
                'data' => $data,
            ]);
            return '';
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
            $data = $this->isDebug() ? [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'message' => $e->getMessage(),
            ] : null;

            $this->json([
                'code' => 404,
                'msg' => '接口不存在',
                'data' => $data,
            ]);
            return '';
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
