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
     * 只记录异常主要的栈信息（文件+方法+行数）到日志
     * @param \Exception $e
     * @return void
     */
    public static function exception(\Exception $e): void
    {
        $info = [];
        $application = [];
        $workerman = [];
        foreach ($e->getTrace() as $item) {
            if (isset($item['file'])) {
                if (strpos($item['file'], 'workerman.phar')) {
                    $workerman[] = sprintf('%s(%d)', $item['function'], $item['line'] ?? -1);
                    continue;
                }
                if (strrpos($item['file'], '/Foundation/Application.php')) {
                    $application[] = sprintf('%s(%d)', $item['function'], $item['line'] ?? -1);
                    continue;
                }
                if (strrpos($item['file'], 'public/index.php') === false) {
                    $info[] = sprintf('%s | %s | %d', $item['file'], $item['function'], $item['line'] ?? -1);
                }
            }
        }
        try {
            if ($application) {
                $info[] = 'Foundation/Application.php => ' . join(' <= ', $application);
            }
            if ($workerman){
                $info[] = 'workerman.phar => ' . join(' <= ', $application);
            }
            self::logger()->error($e->getMessage() . "\n" . print_r($info, true));
        } catch (\Exception $e) {
            ddd('循环异常:', $e->getMessage());
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
        if (is_debug()) {
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