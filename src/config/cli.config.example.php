<?php
$data = include __DIR__ . '/config.php';
/// 前端资源压缩配置
/// https://github.com/matthiasmullie/minify
$data['app']['minify'] = [
    'css' => [
        //    [
        //        'output' => PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.min.css', // 输出文件
        //        'sources' => [
        //            PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.css',
        //            PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/upload.css',
        //        ],
        //    ],
        PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.css',
        PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/upload.css',
    ],
    'js' => [
        PATH_APP_MODULES . 'tao/sdk/phaxui/Layui/index.js', // 默认为同目录
    ]
];
return $data;