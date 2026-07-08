<?php

namespace Phax\Helper;

use Phalcon\Di\Di;
use Phax\Foundation\AppService;

class MyBaseUri
{
    private string $origin = '';

    public function __construct(Di $di)
    {
    }

    public function getOrigin(): string
    {

        if (empty($this->origin)) {
            $this->origin = AppService::config()->getString('app.origin');
            if (empty($this->origin)) {
                throw new \Exception("app.origin not configured");
            }
        }
        return $this->origin;
    }
}