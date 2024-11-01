<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

class SystemRole extends BaseTaoModel
{
    use SoftDelete;

    public string $name = ''; // 会员名称（特殊情况使用）
    public string $title = '';
    public int $sort = 0;
    public int $status = 1; // 0 禁用，1启用（跟原版不同）
    public string $remark = '';

    public function tableTitle(): string
    {
        return '角色';
    }

    /**
     * @throws \Exception
     */
    public function beforeSave(): void
    {
        if (empty($this->title)) {
            throw new \Exception('必须填写角色名称');
        }
        if (!empty($this->name)) {
            if (!preg_match('|^\w+$|', $this->name)) {
                throw new \Exception('角色英文名称只支持字母数字下划线');
            }
            if ($this->getQueryBuilder()->string('name', $this->name)
                ->notEqual('id', $this->id, true)
                ->exits()) {
                throw new \Exception('角色英文名称重复');
            }
        }

        if ($this->getQueryBuilder()->string('title', $this->title)
            ->notEqual('id', $this->id, true)
            ->exits()) {
            throw new \Exception('角色名称重复');
        }
    }


}