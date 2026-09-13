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
    // 按服务器隔离缓存，避免多台服务器互相覆盖 mode/composeCmd
    $id = preg_replace('/[^a-zA-Z0-9_.-]/', '_', cache_server_id());
    return $dir . '/server-' . $id . '.json';
}

/**
 * 获取当前服务器标识（用于缓存指纹）
 * 读取 server.php 中的 host:port，切换服务器时自动失效
 */
function cache_server_id(): string
{
    // 优先使用实际生效的连接（项目级 ssh 覆盖，由 DeployConfig::loadProject 设置）
    $id = getenv('DEPLOY_SERVER_ID');
    if (!empty($id)) {
        return $id;
    }
    // 其次使用当前 env 对应的配置文件（由 CLI 设置），否则用默认 server.php
    $path = getenv('DEPLOY_SERVER_FILE') ?: (DEPLOY_BASE . '/deploy/server.php');
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
 * 项目缓存文件路径（deploy/.cache/<project>.json）
 *
 * 项目级状态：sftp 目录 lastSync 等。带服务器指纹，项目 ssh 目标变更时自动失效。
 */
function project_cache_path(string $projectName): string
{
    $dir = deploy_base_path() . '/.cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . '/' . safe_name($projectName) . '.json';
}

/**
 * 读取项目缓存
 */
function get_project_cache(string $projectName): array
{
    $file = project_cache_path($projectName);
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    // 项目连接目标变更时清空缓存
    if (($data['_server'] ?? '') !== cache_server_id()) {
        unlink($file);
        return [];
    }
    return $data;
}

/**
 * 写入项目缓存（合并更新）
 */
function set_project_cache(string $projectName, array $values): void
{
    $data = get_project_cache($projectName);
    $data['_server'] = cache_server_id();
    $data['_updatedAt'] = date('c');
    foreach ($values as $key => $value) {
        $data[$key] = $value;
    }
    file_put_contents(project_cache_path($projectName), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * 相对路径是否命中排除前缀
 *
 * 匹配规则（$rel 与前缀均为 / 分隔、无首尾斜杠）：
 *   rel === 前缀                → 命中
 *   rel 在前缀目录之下          → 命中（前缀排除整个子树）
 *   前缀在 rel 目录之下         → 不命中（用于目录级剪枝，需继续向下扫描）
 */
function path_matches_excludes(string $rel, array $excludes): bool
{
    foreach ($excludes as $prefix) {
        if ($rel === $prefix || str_starts_with($rel, $prefix . '/')) {
            return true;
        }
    }
    return false;
}

/**
 * 将项目 server.php 中 nginx.ssl 标记置为 true（nginx:ssl 成功后调用）
 *
 * 配置文件需包含 'nginx' => ['ssl' => false] 结构（模板已内置，旧项目配置需手动补）；
 * 返回 false 表示未找到结构，需人工补配置（不影响远程 SSL 已生效）。
 */
function set_project_nginx_ssl(string $projectName): bool
{
    $file = deploy_base_path() . '/projects/' . $projectName . '/server.php';
    if (!file_exists($file)) {
        return false;
    }
    $content = file_get_contents($file);
    if (!preg_match("/'nginx'\s*=>\s*\[/", $content)) {
        return false;
    }
    $updated = preg_replace("/('nginx'\s*=>\s*\[[^]]*'ssl'\s*=>\s*)false/", '$1true', $content, 1, $count);
    if ($count === 0) {
        return true; // 已是 true
    }
    file_put_contents($file, $updated);
    return true;
}

/**
 * 获取缓存的 Docker Compose 命令名
 */
function get_compose_cmd(): string
{
    $cache = get_server_cache();
    return $cache['composeCmd'] ?? 'docker-compose';
}
