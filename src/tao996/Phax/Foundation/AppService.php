<?php

namespace Phax\Foundation;

use Phalcon\Http\Request;
use Phax\Support\Config;

class AppService
{
    /**
     * @return Config
     */
    public static function config(): Config
    {
        return Application::di()->get('config');
    }

    public static function request():Request
    {
        return Application::di()->get('request');
    }
}