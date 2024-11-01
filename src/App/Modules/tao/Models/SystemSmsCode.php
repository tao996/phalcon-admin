<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use App\Modules\tao\Config\Config;

/**
 * 验证码
 */
class SystemSmsCode extends BaseTaoModel
{

    public const int  ReceiverKindPhone = 1;
    public const int  ReceiverKindEmail = 2;

    public const int  SendStatusToSend = 0;
    public const int  SendStatusSuccess = 1;
    public const int  SendStatusFailed = 2;

    // 验证码校验状态
    public const int  StatusNew = 1;
    public const int  StatusDone = 2;

    public int $user_id = 0; // 用户 ID
    // 注意 kind + receiver 是复合索引
    public string $kind = ''; // 短信/邮件类型
    public int $status = 0; // 校验状态
    public int $num = 0; // 错误次数
    public string $send_engine = ''; // 发送引擎
    public int $send_status = 0; // 发送状态
    public int $send_at = 0; // 发送时间
    public string $receiver = ''; // 手机号/邮箱
    public int $receiver_kind = 0;
    public string $code = ''; // 验证码
    public string $data = ''; // 额外信息（最多150个字符）
    public string $ip = ''; // ip 地址


    public function isActive(int $seconds = 0)
    {
        return $this->created_at > 0
            && $this->created_at + ($seconds > 0 ? $seconds : Config::VerifyCodeActiveSeconds) > time()
            && $this->num <= Config::VerifyCodeMaxErrorNum
            && $this->send_status == self::SendStatusSuccess
            && $this->status == self::StatusNew;
    }
}