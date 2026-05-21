<?php

namespace App\Modules\tao;

use App\Modules\tao\Config\Config;
use Phax\Mvc\Model;

class BaseTaoModel extends Model
{

    protected string $tablePrefix = Config::TABLE_PREFIX;

    public int $id = 0;
    public int $created_at = 0; // int(11) default 0 unsigned
    public int $updated_at = 0;
    public int|null $deleted_at = null;

}