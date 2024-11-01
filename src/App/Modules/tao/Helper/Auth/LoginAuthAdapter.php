<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

abstract class LoginAuthAdapter
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }
    abstract public static function check(MyMvcHelper $mvc):bool;

    abstract public function data();

    /**
     * 获取登录用户信息
     * @return SystemUser|null
     */
    abstract public function getUser(): SystemUser|null;

    /**
     * 保存用户信息
     * @param array $user
     * @return mixed 登录标识 token/jwtToken 其它
     */
    abstract public function saveUser(array $user): mixed;

    /**
     * 退出登录
     * @return void
     */
    abstract public function destroy(): void;
}