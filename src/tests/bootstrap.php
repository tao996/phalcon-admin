<?php

define('PATH_ROOT', dirname(__DIR__) . '/');
if (file_exists(__DIR__ . '/bootstrap.test.php')) {
    /**
     * 默认测试所使用配置文件
     * 也可以在 phpunit 中配置
     */
    require_once __DIR__ . '/bootstrap.test.php';
}

require_once PATH_ROOT . 'bootstrap/app.php';

\Phax\Foundation\DiService::with(\Phax\Foundation\Application::di())
    ->db()
    ->pdo()->redis()->cache()
    ->application();
// 测试的模式（需要提前建表）
require_once __DIR__ . '/Unit/TestModel.php';
