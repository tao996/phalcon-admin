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
     * 记录异常的完整调用栈到日志（单行紧凑格式）
     *
     * 格式：`[异常类] 消息 → 文件:行号 | METHOD URI | 请求参数 | 调用栈`
     * 每行均有统一的 `[%date%][%type%]` 前缀，兼容各类日志查看器
     *
     * @param \Throwable $e
     * @return void
     */
    public static function exception(\Throwable $e): void
    {
        try {
            // 请求上下文
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '-';
            $requestUri = $_SERVER['REQUEST_URI'] ?? '-';
            $requestCtx = sprintf('%s %s', $requestMethod, $requestUri);
            if ($requestMethod === 'POST' && !empty($_POST)) {
                $requestCtx .= ' | params=' . json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // 调用栈合并为一行
            $trace = str_replace("\n", ' → ', $e->getTraceAsString());

            $msg = sprintf(
                '[%s] %s → %s(%d) | %s | %s',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $requestCtx,
                $trace
            );
            self::logger()->error($msg);
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
     * @throws \Phalcon\Logger\Exception
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
     * @throws \Phalcon\Logger\Exception
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
     * @throws \Phalcon\Logger\Exception
     */
    public static function error(...$args): void
    {
        if (count($args) === 1) {
            self::logger()->error(print_r($args[0], true));
        } else {
            self::logger()->error(print_r($args, true));
        }
    }

    /**
     * 记录异常详细的栈信息，并重新抛出 $message 异常
     * @param string $message 需要返回给客户端的信息，并重新 throw
     * @param \Exception|null $e 需要记录的异常
     * @throws \Exception
     */
    public static function wrap(string $message, \Exception|null $e, ...$args)
    {
        if (!is_null($e)) {
            self::logger()->error($e->getMessage());
            self::logger()->error($e->getTraceAsString());
        }
        self::error($args);
        throw new \Exception($message);
    }

    /**
     * @param string $message 要返回给客户端显示的信息
     * @param string|array $logMsg 需要记录到日志的信息
     * @param bool $throwMessage 是否再次抛出 $message 异常
     * @throws \Exception
     */
    public static function message(string $message, array|string $logMsg, bool $throwMessage = true): string
    {
        if (is_array($logMsg)) {
            self::logger()->error($message . PHP_EOL . join(PHP_EOL, $logMsg));
        } else {
            self::logger()->error($message . PHP_EOL . $logMsg);
        }
        if ($throwMessage) {
            throw new \Exception($message);
        } else {
            return $message;
        }
    }
}