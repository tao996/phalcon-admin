<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

/**
 * 广告
 */
class CmsAd extends BaseTaoModel
{
    use SoftDelete;

    const array MapKind = [
        1 => '内链', 2 => '外链', 3 => 'ID'
    ];

    public int $user_id = 0;
    public int $begin_at = 0; // 开始时间
    public int $end_at = 0; // 结束时间
    public string $cover = ''; // 封面
    public string $title = ''; // 标题
    public string $link = ''; // 内链/外链/ID
    public int $kind = 0; // link 类型
    // 展示位置
    public int $at_banner = 0; // 横幅
    public int $at_index = 0; // 首页
    public int $at_list = 0; // 列表页
    public int $at_page = 0; // 内页/详情页

    public string $tag = ''; // 标签
    public string $gname = ''; // 分组
    public int $sort = 0; // 排序

    public int $status = 0; // 状态
    public string $remark = ''; // 备注


    public function beforeSave()
    {
        if (empty($this->title) && empty($this->cover)) {
            throw new \Exception('广告标题和图片不能同时为空');
        }
        if (empty($this->link)) {
            throw new \Exception('必须填写链接或路径');
        }
    }

    public static function activeCondition(int $time):string
    {
        return "(begin_at=0 AND end_at=0) OR (begin_at=0 AND end_at >= {$time}) OR (begin_at <= {$time} AND end_at=0) OR (begin_at <= {$time} AND end_at >= {$time})";
    }
}