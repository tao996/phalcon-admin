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
    /**
     * 将 PHP Error / Warning / Notice 转为 ErrorException，
     * 使其能被 try/catch 捕获并进入统一错误处理流程。
     * 不再直接输出 HTML，避免泄露信息到页面。
     */
    function prettyError($errno, $errstr, $errfile, $errline): bool
    {
        // 错误级别被 error_reporting 屏蔽时忽略
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * 输出异常详情（用于 CLI 或调试）
     */
    function prettyException(\Throwable $e): void
    {
        echo sprintf(
            "%s: %s\n  %s(%d)\n  %s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            str_replace("\n", "\n  ", $e->getTraceAsString())
        );
    }
}
