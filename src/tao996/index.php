<?php

if (!defined('PATH_ROOT')) {
    die('you should define the PATH_ROOT first.');
}

if (!defined('IS_WORKER_WEB')) {
    /**
     * workerman/swoole 运行模式
     */
    define('IS_WORKER_WEB', false);
}
/**
 * 传统 php-fpm 模式
 */
define('IS_PHP_FPM', isset($_SERVER['HTTP_HOST']));
const IS_WEB = IS_PHP_FPM || IS_WORKER_WEB;
/**
 * 命令行任务模式
 */
define('IS_TASK', php_sapi_name() === 'cli' && !IS_WEB); // 命令行任务模式


// remove after the package publish release
const PATH_PHAR_SRC = PATH_ROOT . 'phar-src/';
// 所有 PATH_XXX 都需要以 / 结尾
const PATH_CONFIG = PATH_ROOT . 'config/';
const PATH_APP = PATH_ROOT . 'App/';
const PATH_PUBLIC = PATH_ROOT . 'public/';
const PATH_STORAGE = PATH_ROOT . 'storage/';
const PATH_STORAGE_DATA = PATH_STORAGE . 'data/';
const PATH_APP_MODULES = PATH_ROOT . 'app/Modules/';
const PATH_APP_PROJECTS = PATH_ROOT . 'app/Projects/';

// 扩展类库
const PATH_TAO996 = __DIR__ . '/';
const PATH_PHAX = PATH_TAO996 . 'Phax/';
const PATH_PHAR = PATH_TAO996 . 'phar/';
require_once PATH_PHAX . 'function.php';

if (file_exists(PATH_ROOT . 'vendor/autoload.php')) {
    require_once PATH_ROOT . 'vendor/autoload.php';
}

$loader = new \Phalcon\Autoload\Loader();
function loader(): \Phalcon\Autoload\Loader
{
    global $loader;
    return $loader;
}

$loader->setFiles([

    PATH_PHAR . 'dotenv.phar',
], true);

$loader->setNamespaces([
    'App' => PATH_APP,
    'Phax' => PATH_PHAX,
], true);

$loader->register();