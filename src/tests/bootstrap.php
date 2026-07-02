<?php

use Phax\Helper\MyMvc;

define('PATH_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once PATH_ROOT . '/env_loader.php';
if (file_exists(__DIR__ . '/bootstrap.test.php')) {
    /**
     * 默认测试所使用配置文件
     * 也可以在 phpunit 中配置
     */
    require_once __DIR__ . '/bootstrap.test.php';
}
// 自定义配置可以迁移到 bootstrap.test.php 中
// 用于暂时跳过所有的 http 测试（测试内部类时可以关掉）
// const TEST_ORIGIN = 'http://127.0.0.1:8072';
if (!defined('TEST_SKIP_HTTP')) {
    define('TEST_SKIP_HTTP', true); // 有时会造成 gateway 错误
    echo '当前跳过 HTTP 测试', PHP_EOL;
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
/**
 * 一个静态的，用于测试的 mvc
 */
function getMyTestMvc(): \Tests\Helper\MyTestMvc
{
    return \Tests\Helper\MyTestMvc::getInstance();
}

// 测试的模式（需要提前建表）
require_once __DIR__ . '/Unit/TestModel.php';
