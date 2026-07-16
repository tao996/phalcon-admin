<?php
$data = include __DIR__ . '/config.php';
/// 前端资源压缩配置
$data['app']['min'] = [
    PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.css',
    PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/upload.css',
    PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.js',
];
return $data;