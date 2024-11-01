<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

class SystemRoleNode extends BaseTaoModel
{
    public int $role_id = 0;
    public int $node_id = 0;

    public function tableTitle(): string
    {
        return '角色节点关联表';
    }
}