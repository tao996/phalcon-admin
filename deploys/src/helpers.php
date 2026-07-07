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
