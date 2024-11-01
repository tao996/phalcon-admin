<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemRole;

class RoleService
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 获取角色 ID
     * @param array{string} $roles
     * @return array{int}
     * @throws \Exception
     */
    public function getIds(array $roles): array
    {
        if (!empty($roles)) {
            $rows = SystemRole::queryBuilder()->in('name', $roles)
                ->where(['status' => 1])->columns('id')->find();
            return array_column($rows, 'id');
        }
        return [];
    }

    /**
     * 获取正常角色列表
     * @return array{array{id:int,title:string}
     * @throws \Exception
     */
    public function getActiveList(): array
    {
        return SystemRole::queryBuilder()->int('status', 1)
            ->findColumn(['id', 'title'], 'id');
    }
}