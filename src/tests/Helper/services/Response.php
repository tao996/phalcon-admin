<?php

namespace Tests\Helper\services;

use Phalcon\Http\ResponseInterface;

class Response extends \Phalcon\Http\Response
{
    // 不会自动 send 掉
    public function send(): ResponseInterface
    {
        return $this;
    }

    public function getJsonContent(): array
    {
        return json_decode($this->content, true);
    }
}