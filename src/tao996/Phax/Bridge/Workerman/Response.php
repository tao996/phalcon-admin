<?php

namespace Phax\Bridge\Workerman;

use Phalcon\Http\ResponseInterface;
use Phax\Bridge\AbstractResponse;
use Phax\Bridge\AbstractResponseCookies;


class Response extends AbstractResponse
{
    public \Workerman\Connection\TcpConnection $connection;


    private int $_code = 200;
    private string $_message = '';
    private array $_headers = [];
    public array $wsCookies = [];
    public \Workerman\Protocols\Http\Response $response;

    public function __construct(
        \Workerman\Connection\TcpConnection $connection,
        string $content = null,
        $code = null,
        $status = null
    ) {
        parent::__construct($content, $code, $status);
        $this->connection = $connection;
    }

    public function setStatusCode(int $code, string $message = null): ResponseInterface
    {
        $this->_code = $code;
        $this->_message = $message || '';
        return $this;
    }

    public function setHeader(string $name, $value): ResponseInterface
    {
        $this->_headers[$name] = $value;
        return $this;
    }

    public function send(): ResponseInterface
    {
        $this->sent = true;
        $this->response = new \Workerman\Protocols\Http\Response(
            $this->_code,
            $this->_headers,
            $this->getContent()
        );

//        echo '~~~ response send ~~~~', PHP_EOL;
        if ($this->cookies) {
//            var_dump(count($this->cookies->getCookies()));
            foreach ($this->cookies->getCookies() as $cookie) {
                $cookie->send();
//                print_r([
//                    'name' => $cookie->getName(),
//                    'value' => $cookie->getValue(), // 没有加密， path 无效
//                    'class' => get_class($cookie), //  Phax\Bridge\Workerman\Cookie
//                ]);
//                /**
//                 * @var $cookie \Phalcon\Http\Cookie
//                 * bug: 调用 expiration path 等信息后，设置 cookie 失败
//                 */
//                $response->cookie(
//                    $cookie->getName(),
//                    $cookie->getValue(),
//                    $cookie->getExpiration() ?: '',
//                    $cookie->getPath() ?: '/',
////                    $cookie->getDomain(),
////                    $cookie->getSecure(),
////                    $cookie->getHttpOnly(),
////                    $cookie->getOptions()['same_site'] ?? false,
//                );
            }
        }
        $this->connection->send($this->response);
        return $this;
    }


    public function setCookies(\Phalcon\Http\Response\CookiesInterface|null $cookies): ResponseInterface
    {
        /**
         * @var ResponseCookies $cookies
         */
        $this->cookies = $cookies;

//        echo '~~~~ response setCookies ~~~~', PHP_EOL;
        return $this;
    }
}