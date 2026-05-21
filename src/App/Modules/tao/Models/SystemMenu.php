<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

class SystemMenu extends BaseTaoModel
{

    use SoftDelete;

    public int $pid = 0; // 父 ID
    public string $title = ''; // 名称
    public string $icon; // 菜单图标
    public string $href = ''; // 链接地址
    public string $params = ''; // 链接参数（暂时没用到）
    public int $sort = 0; // 菜单排序
    public int $status = 1; // 状态 0 禁用 1 启用

    public int $type = 1; // 多模块，默认为是
    public string $remark = ''; // 备注信息（通常是关键字）
    public string $roles = ''; // 角色限制


    public function tableTitle(): string
    {
        return '菜单';
    }
}