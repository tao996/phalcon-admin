<?php
/**
 * @const IS_DEBUG 是否 debug 状态
 */


if (!function_exists('env')) {
    /**
     * 读取环境变量
     * @param $key
     * @param $default
     * @return array|false|mixed|string|null
     */
    function env($key, $default = null): mixed
    {
        return \Phax\Support\Env::find($key, $default);
    }
}
include_once PATH_ROOT . 'tao996/Phax/Support/Env.php';
if (file_exists(PATH_ROOT . '.env')) {
    Phax\Support\Env::load(PATH_ROOT . '.env');
}

define('IS_DEBUG', \Phax\Support\Env::find('IS_DEBUG', '') === 'true');