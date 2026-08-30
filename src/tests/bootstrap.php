<?php

define('PATH_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);

/**
 * 为了对接口进行测试
 * php -S localhost:9002 -t .\public\ .\public\index.php
 */
const TEST_ORIGIN = 'http://localhost:8071';
require_once PATH_ROOT . 'Kernel.php';
Kernel::with(PATH_ROOT)
    ->setupDisplayErrors()
    ->createTestDi();

// 测试的模式（需要提前建表）
require_once __DIR__ . '/Unit/TestModel.php';
