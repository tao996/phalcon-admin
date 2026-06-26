<?php

namespace Phax\Support\Exception;

/**
 * 普通业务异常
 * 用于表示"预期的、正常的业务逻辑错误"，例如：
 *   - 参数验证失败（"账号不能为空"）
 *   - 业务规则冲突（"邮箱已被占用"）
 *   - 资源不存在（"记录未找到"）
 *   - 权限不足（"不允许删除超级管理员"）
 *
 * 当被 runWeb 捕获时，仅向客户端返回错误消息，不会记录错误日志。
 * 这可以有效减少日志中因普通业务错误产生的噪音，让日志聚焦在真正的系统级异常上。
 */
class BusinessException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
