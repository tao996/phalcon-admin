<?php

/**
 * Deploy 测试引导文件
 */

// 加载 phpseclib（Config 类不直接依赖，但 SSH 等类需要）
$pharPath = __DIR__ . '/../../src/tao996/phar/phpseclib.phar';
if (file_exists($pharPath)) {
    require_once $pharPath;
}

// 加载部署引擎源文件
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/TemplateRenderer.php';
