<?php

namespace Phax\Bridge;

use Phalcon\Di\Di;
use Phax\Foundation\Application;

/**
 * request, response, cookie, view, route, router, url
 * Phalcon\\Http\\Cookie
 * 根据 request 来隔离服务
 */
class HttpDi extends Di
{

    public function __construct()
    {
        $di = Application::di();
        foreach (array_keys($di->services) as $name) {
            if (!$this->has($name)) {
                $this->setShared($name, function () use ($name, $di) {
                    return $di->get($name);
                });
            }
        }
    }

    public function close(): void
    {
        // close mysql?
    }
}