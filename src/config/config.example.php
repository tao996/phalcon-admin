<?php

$data = include_once __DIR__ . '/config-services.example.php';

$data['app'] = array_merge($data['app'], [
    'cdn_locate' => '',
    'hosts' => [
    ],
]);
$data['app']['jwt']['secret'] = 'your new jwt secret';
$data['crypt']['key'] = 'your new crypt key';

return $data;