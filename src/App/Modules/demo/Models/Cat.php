<?php

namespace App\Modules\demo\Models;

use App\Modules\demo\DemoBaseModel;
use Phax\Traits\SoftDelete;

/**
 * 测试 自动时间戳
 */
class Cat extends DemoBaseModel
{
    use SoftDelete;

    protected string|bool $autoWriteTimestamp = 'timestamp';

    public int $id = 0;
    public string $name = '';
    public string $title = '';
    public int $age = 0;

    public function getStatusTextAttr(): string
    {
        return 'this is cat->status_text';
    }

}