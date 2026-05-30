<?php
require_once PATH_ROOT . 'tao996/index.php';

$app = new \Phax\Foundation\Application(PATH_ROOT);
$app->autoloadServices();
if (defined('IS_DEBUG') && IS_DEBUG) {
    // 1. 强制开启错误显示（防止本地 php.ini 误关）
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');

    // 2. 浏览器端才启用 HTML 格式化高亮，CLI/PHPUnit 下保持纯文本
    if (IS_WEB) {
        ini_set('html_errors', '1');
    }

    // 3. 开发环境必须用最高严谨度 E_ALL，连微小的 Notice 都不放过，确保金融数据严丝合缝
    error_reporting(E_ALL);
} else {
    // 4. 安全红线：万一生产环境 php.ini 漏配，代码层做兜底，绝对不向外网暴露任何报错
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
}
// CLI（PHPUnit）下禁用 xdebug 的 HTML 错误输出
if (!IS_WEB && extension_loaded('xdebug')) {
    ini_set('xdebug.mode', 'off');
}

// 自定义美化错误页面（超级好用）
set_error_handler("prettyError");
return $app;