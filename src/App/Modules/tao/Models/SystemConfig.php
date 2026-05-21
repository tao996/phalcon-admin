<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

class SystemConfig extends BaseTaoModel
{
    public string $name = '';
    public string $gname = ''; // 分组名
    public string $value = '';
    public string $remark = '';
    public int $sort = 0;

    public function updateValue(string $gname, string $name, mixed $value)
    {

        return $this->getWriteConnection()->execute(
            'UPDATE ' . $this->getSource() . ' SET value=? WHERE gname=? AND name=?',
            [$value, $gname, $name],
            [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
        );
    }

    public function tableTitle(): string
    {
        return '配置';
    }
}