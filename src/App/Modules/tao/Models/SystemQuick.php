<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

class SystemQuick extends BaseTaoModel
{
    use SoftDelete;

    public int $user_id = 0;
    public string $title = '';
    public string $icon = '';
    public string $href = '';
    public int $sort = 0;
    public int $status = 1;
    public string $remark = '';

    public function tableTitle(): string
    {
        return '快捷菜单';
    }
}