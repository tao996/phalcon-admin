<?php
// 如果需要添加其它的配置信息，将它们放置到 bootstrap.test.php 文件中
// 比如自定义测试的域名，在 src/tests/Helper/MyTestHttpHelper.php 中使用到
// define('TEST_ORIGIN', 'https://admin.local.test/');

$data = require_once PATH_CONFIG . 'config.php';
return $data;