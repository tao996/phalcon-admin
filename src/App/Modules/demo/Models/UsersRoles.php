<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;

/**
 * 会员与角色中间表（为了测试，特意不命名为 UserRole）
 */
class UsersRoles extends DemoBaseModel
{
    protected string|bool $autoWriteTimestamp = false;

    public string $table = 'user_role';

    public int $user_id = 0;
    public int $role_id = 0;
}