<?php

namespace App\Modules\tao\Helper;

use Phax\Support\Exception\BlankException;

class ResponseHelper
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 设置跳转
     */
    public function redirectIn(string $action, string $controller = ''): void
    {
        if ($controller == '') {
            $controller = $this->mvc->router()->getControllerName();
        }
        $this->mvc->response()
            ->redirect($controller . '/' . $action)
            ->send();
    }

    /**
     * 地址跳转
     * @param $location
     * @param bool $externalRedirect
     * @param int $statusCode
     * @return void
     */
    public function redirect($location = null, bool $externalRedirect = false, int $statusCode = 302): void
    {
        $this->mvc->response()
            ->redirect($location, $externalRedirect, $statusCode)
            ->send();
    }

    /**
     * 发送内容
     * @param mixed $data 响应的内容，如果为数组则自动转为 json
     * @param int $code
     * @return \Phalcon\Http\ResponseInterface
     */
    public function send(mixed $data, int $code = 200)
    {
        return $this->mvc->response()
            ->setStatusCode($code)
            ->setContent(is_array($data) ? json_encode($data) : $data)
            ->send();
    }


// https://stackoverflow.com/questions/2310558/how-to-delete-all-cookies-of-my-website-in-php

    /**
     * 移除 cookie
     * @param string $delName 如果为空，则移除全部 cookie
     * @return void
     */
    public function cookieRemove(string $delName = ''): void
    {
        if ($this->mvc->request()->hasServer('HTTP_COOKIE')) {
            $cookies = explode(';', $this->mvc->request()->getServer('HTTP_COOKIE'));
            $delAll = empty($delName);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                if ($delAll || $delName == $name) {
                    setcookie($name, '', time() - 1000);
                    setcookie($name, '', time() - 1000, '/');
                    if (!$delAll) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param string $name
     * @param $value
     * @param int $expire 过期时间，注意需要 time()+3600 表示1小时
     * @return void
     */
    public function cookieSet(string $name, $value, int $expire = 0): void
    {
        $cc = $this->mvc->config()->path('cookie')->toArray();
        $this->mvc->cookies()->set(
            $name,
            $value,
            $expire,
            '/',
            $cc['secret'] ?? null,
            $cc['domain'] ?? null,
            true
        );
    }

    /**
     * 渲染指定路径的视图模板
     * @param string $pathTpl 模板路径
     * @param array $data
     * @return string
     */
    public function simpleView(string $pathTpl, array $data = []): string
    {
        $simpleView = new \Phalcon\Mvc\View\Simple();
        return $simpleView->render($pathTpl, $data);
    }

    function json($data): \Phalcon\Http\Response|\Phalcon\Http\ResponseInterface
    {
        $this->mvc->response()
            ->setContentType('application/json', 'UTF-8')
            ->setContent(json_encode($data))
            ->send();
        throw new BlankException('');
    }
}