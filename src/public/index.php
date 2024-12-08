<?php

//phpinfo();exit;
const PRINT_REQUEST_TIME = false;
define('PATH_ROOT', dirname(__DIR__) . '/');
$start_time = PRINT_REQUEST_TIME ? microtime(true) : 0;
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
if (PRINT_REQUEST_TIME) {
    $end_time = microtime(true);
    echo 'spend: ' . ($end_time - $start_time) . 's', PHP_EOL;
}
