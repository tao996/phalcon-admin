<?php

namespace Phax\Support;

class Env
{
    /**
     * 加载指定的 .env 文件（纯 PHP 实现，替代 Symfony Dotenv）
     * 支持语法：
     *   KEY=VALUE
     *   KEY="quoted value"
     *   KEY='quoted value'
     *   export KEY=VALUE
     *   # comment
     *   ${VAR} / $VAR 变量替换
     *
     * @param string $pathEnv .env 文件路径
     * @return void
     */
    public static function load(string $pathEnv = ''): void
    {
        if (empty($pathEnv) || !file_exists($pathEnv)) {
            return;
        }

        $lines = file($pathEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        $vars = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // 跳过空行和注释
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // 去掉 export 前缀
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            // 分割 key=value（限制 2 段，避免值中的 = 被分割）
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            // 去除引号
            $value = self::unquote($value);

            // 变量替换：${VAR} 或 $VAR
            $value = self::resolveVariables($value, $vars);

            // 写入 $_ENV 和 putenv（覆盖已有值，与 Dotenv::overload 行为一致）
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
            $vars[$key] = $value;
        }
    }

    /**
     * 从 $_ENV 中读取环境变量
     * @param string $name
     * @param mixed|null $value
     * @return mixed
     */
    public static function find(string $name, mixed $value = null): mixed
    {
        return $_ENV[$name] ?? $value;
    }

    /**
     * 去除值的引号（支持单引号和双引号）
     */
    private static function unquote(string $value): string
    {
        if (strlen($value) < 2) {
            return $value;
        }
        $first = $value[0];
        $last = $value[-1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }
        return $value;
    }

    /**
     * 替换字符串中的 ${VAR} 和 $VAR 变量引用
     */
    private static function resolveVariables(string $value, array $vars): string
    {
        // 替换 ${VAR} 格式
        $value = preg_replace_callback('/\$\{([^}]+)\}/', function ($matches) use ($vars) {
            return $vars[$matches[1]] ?? $_ENV[$matches[1]] ?? getenv($matches[1]) ?: $matches[0];
        }, $value);

        // 替换 $VAR 格式（限定为简单变量名，避免与 ${} 冲突）
        $value = preg_replace_callback('/\$([a-zA-Z_]\w*)/', function ($matches) use ($vars) {
            return $vars[$matches[1]] ?? $_ENV[$matches[1]] ?? getenv($matches[1]) ?: $matches[0];
        }, $value);

        return $value;
    }
}
