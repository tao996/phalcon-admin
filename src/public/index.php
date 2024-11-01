<?php

//phpinfo();exit;
define('PATH_ROOT', dirname(__DIR__) . '/');

try {
    // 此时还没有处理到异常
    $app = require_once PATH_ROOT . 'bootstrap/app.php';
} catch (\Exception $e) {
    echo $e->getMessage();
    return;
}

/**
 * @var $app \Phax\Foundation\Application
 */
if (IS_PHP_FPM) {
    $app->runWeb();
} else {
    die('only run on php-fpm');
}
