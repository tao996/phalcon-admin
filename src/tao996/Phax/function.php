<?php


if (!function_exists('pr')) {
    /**
     * print only value (if run in cli, the print in console)
     * @param $var
     * @return void
     * @throws Exception
     */
    function pr($var): void
    {
        echo IS_TASK ? '|<--- ' . PHP_EOL : '<pre>';
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
if (!function_exists('prettyError')) {
    function prettyError($errno, $errstr, $errfile, $errline)
    {
        echo <<<HTML
<style>
    .php-error { background: #1e1e1e; color: #f8f8f2; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 14px; line-height: 1.6; }
    .php-error b { color: #fc4a5a; }
    .php-error .file { color: #a6e22e; }
    .php-error .line { color: #fd971f; }
</style>
<div class="php-error">
    <b>File:</b> <span class="file">$errfile</span><br>
    <b>Line:</b> <span class="line">$errline</span><br>
    <b>No:</b> $errno<br>
    <b>Error:</b> <pre>$errstr</pre>
</div>
HTML;
        exit;
    }

    function prettyException(\Exception $e)
    {
        prettyError($e->getCode(), $e->getMessage() . PHP_EOL . $e->getTraceAsString(), $e->getFile(), $e->getLine());
    }
}
