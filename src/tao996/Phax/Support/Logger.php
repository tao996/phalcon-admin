<?php

namespace Phax\Support;

use Phax\Foundation\Application;

class Logger
{


    private static function logger(): \Phalcon\Logger\Logger
    {
        static $logger = null;
        if (is_null($logger)) {
            $logger = Application::di()->get('logger');
        }
        return $logger;
    }


    /**
     * 记录异常的完整调用栈到日志（JSON Lines 格式）
     * 安装 https://github.com/jqlang/jq/releases 工具查看
     * windows 安装 git bash 然后使用 `tail -f app.log | jq`  实时查看日志
     *
     * 输出示例（紧凑为一行）：
     * {"time":"...","level":"ERROR","type":"exception","class":"RuntimeException",
     *  "message":"数据库连接失败","file":"/app.php","line":42,
     *  "request":{"method":"GET","uri":"/api/users"},
     *  "trace":["#0 /vendor/file.php(123)","#1 /src/main.php(45)"]}
     *
     * @param \Throwable $e
     * @param array $context 更多补充信息
     * @return void
     */
    public static function exception(\Throwable $e, array $context = []): void
    {
        try {
            // 请求上下文
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '-';
            $requestUri = $_SERVER['REQUEST_URI'] ?? '-';
            $request = [
                'method' => $requestMethod,
                'uri' => $requestUri,
            ];
            if ($requestMethod === 'POST' && !empty($_POST)) {
                $request['params'] = $_POST;
            }

            $data = [
                'type' => 'exception',
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request,
            ];

            // 记录上下文信息
            if (!empty($context)) {
                $data['context'] = $context;
            }
            $data['trace'] = explode("\n", $e->getTraceAsString());
            // JsonFormatter 会自动注入 time 和 level 字段
            self::logger()->error(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $inner) {
            // 日志异常静默处理，防止循环出错；
            // 用 error_log() 作为回退，至少让外部能感知到 logger 本身出错了
            error_log(
                '[Logger::exception FAILED] ' . get_class($inner) . ': ' . $inner->getMessage()
                . ' at ' . $inner->getFile() . '(' . $inner->getLine() . ')'
            );
        }
    }

    /**
     * 只记录消息到日志中
     * @param ...$args
     * @return void
     */
    public static function info(...$args): void
    {
        if (count($args) === 1) {
            self::logger()->info(print_r($args[0], true));
        } else {
            self::logger()->info(print_r($args, true));
        }
    }

    public static function debug(...$args): void
    {
        if (IS_DEBUG) {
            if (count($args) === 1) {
                self::logger()->debug(print_r($args[0], true));
            } else {
                self::logger()->debug(print_r($args, true));
            }
        }
    }

    /**
     * 只记录警告级别信息
     * @param ...$args
     * @return void
     */
    public static function warning(...$args): void
    {
        if (count($args) === 1) {
            self::logger()->warning(print_r($args[0], true));
        } else {
            self::logger()->warning(print_r($args, true));
        }
    }

    /**
     * 只记录错误级别信息
     * @param ...$args
     * @return void
     */
    public static function error(...$args): void
    {
        try {
            if (count($args) === 1) {
                self::logger()->error(print_r($args[0], true));
            } else {
                self::logger()->error(print_r($args, true));
            }
        } catch (\Throwable $inner) {
            error_log(
                '[Logger::error FAILED] ' . get_class($inner) . ': ' . $inner->getMessage()
                . ' at ' . $inner->getFile() . '(' . $inner->getLine() . ')'
            );
        }
    }


    /**
     * 在日志中记录普通信息
     * @param ...$args
     * @return void
     */
    public static function notice(...$args): void
    {
        if (count($args) === 1) {
            self::logger()->notice(print_r($args[0], true));
        } else {
            self::logger()->notice(print_r($args, true));
        }
    }
}