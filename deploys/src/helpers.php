<?php

/**
 * 部署工具辅助函数
 */

/**
 * 获取部署工具根目录
 */
function deploy_base_path(): string
{
    return dirname(__DIR__);
}

/**
 * 将数组中的键用点号访问（如 'app.title' → $cfg['app']['title']）
 */
function array_get(array $array, string $key, mixed $default = null): mixed
{
    $keys = explode('.', $key);
    $current = $array;
    foreach ($keys as $k) {
        if (!is_array($current) || !array_key_exists($k, $current)) {
            return $default;
        }
        $current = $current[$k];
    }
    return $current;
}

/**
 * 递归合并数组（与 array_merge_recursive 不同：同名键覆盖而非合并）
 */
function array_merge_deep(array ...$arrays): array
{
    $result = [];
    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                $result[$key] = array_merge_deep($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }
    }
    return $result;
}

/**
 * 生成安全的目录名（用于项目标识）
 */
function safe_name(string $name): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
}

/**
 * 在本地打印信息（CLI 输出）
 */
function deploy_log(string $message, string $type = 'info'): void
{
    $prefix = match ($type) {
        'info' => "\033[36mℹ\033[0m",
        'ok' => "\033[32m✔\033[0m",
        'warn' => "\033[33m⚠\033[0m",
        'error' => "\033[31m✘\033[0m",
        'step' => "\033[34m→\033[0m",
        'cmd' => "\033[90m$\033[0m",
        default => "\033[36mℹ\033[0m",
    };
    echo sprintf("  %s %s\n", $prefix, $message);
}

/**
 * 缓存文件路径
 */
function cache_file_path(): string
{
    $dir = deploy_base_path() . '/.cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/server.json';
}

/**
 * 获取当前服务器标识（用于缓存指纹）
 * 读取 server.php 中的 host:port，切换服务器时自动失效
 */
function cache_server_id(): string
{
    $path = DEPLOY_BASE . '/deploys/server.php';
    if (!file_exists($path)) {
        return 'unknown';
    }
    $cfg = require $path;
    $host = $cfg['ssh']['host'] ?? '';
    $port = $cfg['ssh']['port'] ?? 22;
    return $host . ':' . $port;
}

/**
 * 读取服务器缓存
 */
function get_server_cache(): array
{
    $file = cache_file_path();
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    // 如果服务器指纹不匹配，清空缓存
    if (($data['_server'] ?? '') !== cache_server_id()) {
        unlink($file);
        return [];
    }
    return $data;
}

/**
 * 写入服务器缓存
 */
function set_server_cache(array $values): void
{
    $data = get_server_cache();
    $data['_server'] = cache_server_id();
    $data['_updatedAt'] = date('c');
    foreach ($values as $key => $value) {
        $data[$key] = $value;
    }
    file_put_contents(cache_file_path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 获取缓存的 Docker Compose 命令名
 */
function get_compose_cmd(): string
{
    $cache = get_server_cache();
    return $cache['composeCmd'] ?? 'docker-compose';
}
