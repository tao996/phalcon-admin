<?php

//phpinfo();exit;
/**
 * @const IS_DEBUG 是否 debug 状态
 */
define('PATH_ROOT', dirname(__DIR__) . '/');

// if you want to debug with phalcon (too many compatible between cphalcon:5.8 and [phalcon](https://github.com/phalcon/phalcon))
// run `php artisan phalcon` to disable the default phalcon extension
// then restart the image server
if (!extension_loaded('phalcon')) {
    require_once PATH_ROOT . 'phar-src/index.php';
}

try {
    // 此时还没有处理到异常
    $app = require_once PATH_ROOT . 'bootstrap/app.php';
} catch (\Exception $e) {
    echo $e->getMessage();
    return;
}
if (!defined('IS_DEBUG')){
    define("IS_DEBUG", false);
}
/**
 * @var $app \Phax\Foundation\Application
 */
if (IS_PHP_FPM) {
    $app->runWeb();
} else {
    die('only run on php-fpm');
}
