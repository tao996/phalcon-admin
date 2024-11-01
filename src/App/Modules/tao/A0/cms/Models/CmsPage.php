<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

class CmsPage extends BaseTaoModel
{
    use SoftDelete;

    public string $tag = ''; // 标签
    public string $name = ''; // 名称
    /**
     * 中文标题
     * @var string
     */
    public string $title = '';
    public string $description = ''; // 描述
    public int $sort = 0; // 菜单排序
    public int $status = 0; // 状态 0 禁用 1 启用

    public int $content_id = 0;

    public function tableTitle(): string
    {
        return '单页';
    }

    public function isRepeat(): bool
    {
        $q = self::queryBuilder()
            ->where(['tag' => $this->tag,  'name' => $this->name]);
        if ($this->id > 0) {
            $q->notEqual('id', $this->id);
        }
        return $q->exits();
    }
}