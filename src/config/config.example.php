<?php

$data = include_once PATH_CONFIG . 'config-services.example.php';

$data['app'] = array_merge($data['app'], [
    'name' => env('APP_NAME', 'Phalcon Admin'),
    'cdn_locate' => '',
    'hosts' => [
    ],
]);
$data['app']['jwt']['secret'] = 'your new jwt secret';
$data['crypt']['key'] = 'your new crypt key';

return $data;