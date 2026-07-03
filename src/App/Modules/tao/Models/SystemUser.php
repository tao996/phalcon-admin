<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use App\Modules\tao\Services\RoleService;
use Phax\Support\Exception\BusinessException;
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
    public int $status = 1; // 状态:1启用

    public const int  STATUS_DELETE = 100; // 账号被禁用/删除

    public function tableTitle(): string
    {
        return '用户';
    }

    /**
     * 第三方平台绑定列表
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public function binds(): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return $this->hasMany('id', SystemUserBind::class, 'user_id');
    }

    public function roleIds(): array
    {
        return $this->role_ids ? explode(',', $this->role_ids) : [];
    }

    /**
     * 返回用户的角色
     * @return array<SystemRole>
     */
    public function roles(): array
    {
        return RoleService::getRolesByIds($this->roleIds());
    }

    public function addAccount(string $account, string $kind, int $validAt = 0)
    {
        if ($kind == 'email') {
            $this->email = $account;
            $this->email_at = $validAt;
            $this->email_valid = true;
        } elseif ('phone' == $kind) {
            $this->phone = $account;
            $this->phone_at = $validAt;
            $this->phone_valid = true;
        } else {
            throw new BusinessException('不支持的账号类型', [
                'kind' => $kind,
            ]);
        }
    }

    public function getAccountByType(string $type): string
    {
        if ($type == 'email') {
            return $this->email;
        } elseif ($type == 'phone') {
            return $this->phone;
        } else {
            throw new BusinessException('不支持的账号类型', [
                'type' => $type,
            ]);
        }
    }

    public function beforeCreate()
    {
        // https://docs.phalcon.io/5.0/en/support-helper#random
        if (empty($this->seed)) {
            $this->seed = MyHelperFacade::random(); // 默认 字母数字，长度8
        }
    }


}