<?php

namespace App\Modules\tao\A0\cms\Models;


use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;
use Phax\Utils\MyData;

class CmsCategory extends BaseTaoModel
{
    use SoftDelete;

    const int KindList = 1; // 列表文章

    public int $kind = 0;
    public int $pid = 0; // 父级 ID
    public string $pids = ''; // 父级 ID 链
    public string $title = ''; // 标题
    public string $name = ''; // 名称
    public string $cover = ''; // 栏目封面
    public string $summary = ''; // 栏目简介

    public string $tag = ''; // 自定义标签
    public int $navbar = 0; // 导航展示
    public int $sort = 0; // 排序
    public int $status = 0; // 状态
    public string $other = ''; // 其它内容（待使用）

    public string $tpl = ''; // 发布模板？

    public int $content_id = 0;
    public string $image_ids = ''; // 图片 ID

    /**
     * @throws \Exception
     */
    public static function mapKind(int $kind = 0)
    {
        static $kinds = [self::KindList => '文章列表'];
        return MyData::getMapData($kinds,$kind);
    }

}