<?php

namespace Phax\Support\Exception;

use Phax\Support\Logger;

/**
 * 业务异常 + 自动日志记录
 *
 * 向前端返回友好的错误消息（与 BusinessException 一样），
 * 同时将完整的错误堆栈和补充信息记录到日志文件中。
 *
 * 适用于：预期内的错误，但需要保留现场以便排查。
 *
 * 使用示例：
 * ```php
 * throw new LogException('创建订单失败',
 *     context: ['order_id' => 123, 'user_id' => $userId]
 * );
 * ```
 */
class LogException extends BusinessException
{
    /**
     * 同时记录自身堆栈信息
     * @param string $message 向前端展示的错误消息
     * @param array $context 补充上下文（记录到日志）
     * @param int $code 错误码
     * @param \Throwable|null $previous 原始异常链
     */
    public function __construct(
        string                 $message = '',
        private readonly array $context = [],
        int                    $code = 0,
        ?\Throwable            $previous = null
    )
    {
        parent::__construct($message, $this->context, $code, $previous);
        $this->log();
    }

    /**
     * 获取补充上下文
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 将异常记录到日志（自动在构造函数中调用）
     */
    private function log(): void
    {
        try {
            // 记录异常堆栈
            Logger::exception($this, $this->context);
        } catch (\Throwable) {
            // 日志异常静默处理，防止循环出错
        }
    }
}
