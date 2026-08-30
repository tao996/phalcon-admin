<?php
// 如果需要添加其它的配置信息，将它们放置到 bootstrap.test.php 文件中
// 比如自定义测试的域名，在 src/tests/Helper/MyTestHttpHelper.php 中使用到

/*
 * 在 phpunit.xml 中添加
<php>
    <env name="PATH_CONFIG" value="tests/config.php"/>
</php>
 */
$data = require_once PATH_CONFIG . 'config.php';
$data['database']['stores']['mysql']['dbname'] = 'phalcon-admin-test';
return $data;