<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

class SystemMigration extends BaseTaoModel
{

    public string $version = ''; // 唯一
    public string $summary = ''; // 更新的内容
}