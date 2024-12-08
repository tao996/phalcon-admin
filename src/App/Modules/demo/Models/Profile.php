<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;

class Profile extends DemoBaseModel
{
    public int $id = 0;
    public int $user_id = 0;
    public int $age = 0;
    public string $remark = '';
}