<?php
$data = include __DIR__ . '/config.php';
/// 前端资源压缩配置
/// https://github.com/matthiasmullie/minify
$data['app']['minify'] = [
    'css' => [
        PATH_APP_MODULES . 'tao/views/assets/layui/index.css',
        PATH_APP_MODULES . 'tao/views/assets/layui/upload.css',
    ],
    'js' => [
        PATH_APP_MODULES . 'tao/views/assets/layui/index.js', // 默认为同目录
    ]
];
return $data;