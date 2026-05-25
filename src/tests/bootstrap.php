<?php

define('PATH_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
if (file_exists(__DIR__ . '/bootstrap.test.php')) {
    /**
     * 默认测试所使用配置文件
     * 也可以在 phpunit 中配置
     */
    require_once __DIR__ . '/bootstrap.test.php';
}
// 用于暂时跳过所有的 http 测试（测试内部类时可以关掉）
if (!defined('TEST_SKIP_HTTP')) {
    define('TEST_SKIP_HTTP', false);
}
require_once PATH_ROOT . 'bootstrap/app.php';
$di = \Phax\Foundation\Application::di();
$di->setShared('request', function () {
    return new \Tests\Helper\services\Request();
});
$di->setShared('response', function () {
    return new \Tests\Helper\services\Response();
});
$di->setShared('session', function () {
    return new \Tests\Helper\services\Session();
});
\Phax\Foundation\DiService::with($di)
    ->db()
    ->pdo()->redis()->cache()
    ->application();
// 测试的模式（需要提前建表）
require_once __DIR__ . '/Unit/TestModel.php';
