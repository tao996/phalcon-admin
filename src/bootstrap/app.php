<?php
require_once PATH_ROOT . 'tao996/index.php';

$app = new \Phax\Foundation\Application(PATH_ROOT);
$app->autoloadServices();
if (IS_DEBUG) {
// 开启错误显示 + 美化格式化
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}
// 自定义美化错误页面（超级好用）


set_error_handler("prettyError");
return $app;