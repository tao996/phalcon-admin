<?php

namespace Phax\Bridge\Workerman;

use Phax\Bridge\AbstractCookie;

/**
 * 对应 \Phalcon\Http\Cookie
 */
class Cookie extends AbstractCookie
{

    protected function values(): array
    {
//        var_dump('~~~~~',__METHOD__, get_class($this->httpDi));
        /**
         * @var $request Request
         * 如果出现找不到 httpDi，通常在是 AbstractResponseCookies 中没有调用 setHttpDi
         */
        $request = $this->httpDi->get('request');
        return $request->cookieData;
    }

    /**
     * 用于替换默认的标准函数 'setcookie'
     */
    protected function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = '',
        string $priority = ''
    ): void {
//        echo '~~~~ setCookie:', __METHOD__, PHP_EOL;
//        print_r([$name, $value]);

        /**
         * @var $response Response
         */
        $response = $this->httpDi->getShared('response');
        $response->response->cookie(
            $name,
            $value,
            $expire ?: null,
            $path ?: '/',
            $domain ?: '',
            $secure ?: false,
            $httponly ?: false,
            $samesite ?: false,
        );
    }
}