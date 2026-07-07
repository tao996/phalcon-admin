<?php

/**
 * 应用主配置 — 由 deploy 工具生成
 *
 * 从 config.example.php 渲染而来，覆盖生产环境值
 */
$data = include __DIR__ . '/services.docker.example.php';

// 生产安全设置
$data['app'] = array_merge($data['app'] ?? [], [
    'title' => '{{APP_TITLE}}',
    'origin' => '{{APP_ORIGIN}}',
    'https' => {{APP_HTTPS}},
    'demo' => false,
    'superAdmin' => [1],
    'jwt' => [
        'hmac' => 'sha256',
        'secret' => '{{JWT_SECRET}}',
        'expire' => 3600 * 24,
        'subject' => 'jwt',
    ],
    'sites' => [
{{APP_SITES}}
    ],
]);

// 数据库配置（使用 Docker 内部网络）
$data['database']['host'] = 'mysql';
$data['database']['dbname'] = '{{MYSQL_DATABASE}}';
$data['database']['username'] = '{{MYSQL_USER}}';
$data['database']['password'] = '{{MYSQL_PASSWORD}}';

// Redis 配置
$data['redis']['host'] = 'redis';
$data['redis']['password'] = '{{REDIS_PASSWORD}}';

return $data;
