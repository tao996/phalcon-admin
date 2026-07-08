<?php

namespace Phax\Foundation;

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
}