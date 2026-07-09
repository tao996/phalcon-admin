<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemRole;

class RoleService
{

    /**
     * 获取角色 ID
     * @param array{string} $roles
     * @return array{int}
     * @throws \Exception
     */
    public static function getIds(array $roles): array
    {
        if (!empty($roles)) {
            $rows = SystemRole::queryBuilder()
                ->in('name', $roles)
                ->where(['status' => 1])->columns('id')->find();
            return array_column($rows, 'id');
        }
        return [];
    }

    public static function getRoles(): array
    {
        static $roles = null;
        if ($roles === null) {
            $roles = SystemRole::find()->toArray();
        }
        return $roles;
    }

    /**
     * 获取用户的角色
     * @param array $roleIds
     * @return array
     */
    public static function getRolesByIds(array $roleIds): array
    {
        return array_filter(self::getRoles(), function ($role) use ($roleIds) {
            return in_array($role['id'], $roleIds);
        });
    }

    /**
     * 获取正常角色列表
     * @return array{array{id:int,title:string}
     * @throws \Exception
     */
    public static function getActiveList(): array
    {
        return SystemRole::queryBuilder()
            ->int('status', 1)
            ->findColumn(['id', 'title'], key:'id');
    }
}