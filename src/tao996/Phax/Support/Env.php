<?php

namespace Phax\Support;

use Symfony\Component\Dotenv\Dotenv;

class Env
{
    /**
     * 加载指定的 .env 文件
     * @param string $pathEnv 默认为 PATH_ROOT.'.env'
     * @return void
     */
    public static function load(string $pathEnv = ''): void
    {
        $dotenv = new Dotenv();
        $dotenv->overload($pathEnv);
    }

    public static function find($name, $value = null)
    {
        return $_ENV[$name] ?? $value;
    }
}