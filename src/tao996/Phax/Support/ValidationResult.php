<?php

namespace Phax\Support;

/**
 * 链式验证的结果对象
 * 由 Validate::validate() 返回，提供 isValid() 和 errors 属性
 */
class ValidationResult
{
    /**
     * @param array|null $errors 验证失败时的错误消息列表，验证通过为 null
     */
    public function __construct(public readonly ?array $errors = null)
    {
    }

    /**
     * 验证是否通过
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->errors === null || $this->errors === [];
    }
}
