<?php

namespace Phax\Bridge;

use Phalcon\Http\ResponseInterface;
use Phax\Bridge\Traits\HttpDiTrait;

abstract class AbstractResponse extends \Phalcon\Http\Response implements ResponseInterface
{
    use HttpDiTrait;
    public function setStatusCode(int $code, string $message = null): ResponseInterface
    {
        throw new \Exception('TODO: Implement setStatusCode() method.');
    }

    public function setHeader(string $name, $value): ResponseInterface
    {
        throw new \Exception('TODO: Implement setHeader() method.');
    }

    public function send(): ResponseInterface
    {
        throw new \Exception('TODO:  Implement send() method.');
    }
}