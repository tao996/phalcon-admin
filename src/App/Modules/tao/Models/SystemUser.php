<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Support\Facade\MyHelperFacade;
use Phax\Traits\SoftDelete;

class SystemUser extends BaseTaoModel
{
    use SoftDelete;

    public string $role_ids = ''; // 角色权限ID
    public string $seed = ''; // 随机数

    public string $password = ''; // 登录密码
    public string $email = ''; // 登录邮箱（唯一）
    public int $email_at = 0; // 修改登录邮箱时间
    public int $email_valid = 0; // 电子邮箱是否验证
    public string $phone = ''; // 手机号（唯一）
    public int $phone_at = 0;
    public int $phone_valid = 0; // 手机号是否验证

    public string $nickname = ''; // 用户昵称
    public string $head_img = ''; // 头像
    public string $signature = ''; // 签名
    public string $binds = '[]'; // 第三方账号绑定
    public int $status = 1; // 状态:1启用

    public const int  STATUS_DELETE = 100; // 账号被禁用/删除

    public function tableTitle(): string
    {
        return '用户';
    }

    public function roleIds(): array
    {
        return $this->role_ids ? explode(',', $this->role_ids) : [];
    }


    public function beforeCreate()
    {
        // https://docs.phalcon.io/5.0/en/support-helper#random
        if (empty($this->seed)) {
            $this->seed = MyHelperFacade::random(); // 默认 字母数字，长度8
        }
    }

}