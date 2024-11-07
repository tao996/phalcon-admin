<?php

$data = include_once PATH_CONFIG . 'config-services.example.php';

$data['app'] = array_merge($data['app'], [
    'title' => 'Phalcon Admin Dev',
//    'cdn_locate' => 'ncn',
//    'https' => true,
    'hosts' => [
    ],
]);
// must modify
$data['app']['jwt']['secret'] = 'phalcon';
$data['crypt']['key'] = '123456'; // 只能修改一次，否则加密的账号信息解密失败

// only for dev
$data['metadata']['driver'] = 'memory';
$data['database']['log']['driver'] = 'file';

return $data;