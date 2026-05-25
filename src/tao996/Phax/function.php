<?php

if (!function_exists('is_debug')) {
    function is_debug(): bool
    {
        return defined('IS_DEBUG') && IS_DEBUG;
    }
}

if (!function_exists('pr')) {
    /**
     * print only value (if run in cli, the print in console)
     * @param $var
     * @return void
     * @throws Exception
     */
    function pr($var): void
    {
        echo IS_TASK  ? '|<--- ' . PHP_EOL : '<pre>';
        foreach (func_get_args() as $arg) {
            print_r($arg);
            echo IS_TASK ? PHP_EOL : '<br/>';
        }
        echo IS_TASK ? '|---> ' . PHP_EOL : '</pre>';
        if (func_get_args()[func_num_args() - 1] !== false) {
            exit();
        }
    }
}

if (!function_exists('ddd')) {
    /**
     * print var type and value, and stop
     */
    function ddd($var): void
    {
        if (class_exists('\Phalcon\Support\Debug\Dump')) {
            $args = func_get_args();
            array_map(function ($x) {
                $string = (new \Phalcon\Support\Debug\Dump())->variable($x);
                echo IS_PHP_FPM ? $string : strip_tags($string) . PHP_EOL;
            }, $args);
            exit();
        } else {
            pr(func_get_args());
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
