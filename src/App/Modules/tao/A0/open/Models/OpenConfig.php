<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseTaoModel;

class OpenConfig extends BaseTaoModel
{
    protected string|bool $autoWriteTimestamp = false;

    public string $name = '';
    public string $value = '';
    public string $remark = '';

}