<?php

/**
 * 用于在程序中代替 die/exit
 */

use Phax\Foundation\Application;

if (!function_exists('appExit')) {
    function appExit(string $message = ''): void
    {
        if (IS_WORKER_WEB) {
            ob_clean();
            throw new \Exception('SORRY:'.$message);
        } else {
            exit($message);
        }
    }
}

if (!function_exists('is_debug')) {
    function is_debug(): bool
    {
        return defined('IS_DEBUG') && IS_DEBUG;
    }
}


function ddd($var): void
{
    $args = func_get_args();
    array_map(function ($x) {
        $string = (new \Phalcon\Support\Debug\Dump())->variable($x);
        echo IS_TASK || IS_WORKER_WEB ? strip_tags($string) . PHP_EOL : $string;
    }, $args);
    appExit();
}

// 接口测试专用
if (!function_exists('ppp')) {
    function ppp($var): void
    {
        $args = func_get_args();
        array_map(function ($x) {
            $string = (new \Phalcon\Support\Debug\Dump())->variable($x);
            echo strip_tags($string) . PHP_EOL;
        }, $args);
        appExit();
    }
}

if (!function_exists('pr')) {
    function pr($var): void
    {
        echo IS_TASK || IS_WORKER_WEB ? '|<--- ' . PHP_EOL : '<pre>';
        foreach (func_get_args() as $arg) {
            print_r($arg);
            echo IS_TASK || IS_WORKER_WEB ? PHP_EOL : '<br/>';
        }
        echo IS_TASK || IS_WORKER_WEB ? '|---> ' . PHP_EOL : '</pre>';
        if (func_get_args()[func_num_args() - 1] === false) {
            if (IS_WORKER_WEB) {
                ob_flush();
            }
            return;
        } else {
            appExit();
        }
    }
}


if (!function_exists('env')) {
    /**
     * 读取环境变量
     * @param $key
     * @param $default
     * @return array|false|mixed|string|null
     */
    function env($key, $default = null)
    {
        return \Phax\Support\Env::find($key, $default);
    }
}
