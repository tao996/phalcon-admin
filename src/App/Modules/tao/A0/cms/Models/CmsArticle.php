<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;
use Phax\Utils\MyData;

class CmsArticle extends BaseTaoModel
{
    use SoftDelete;

    const int CheckStatusTodo = 1;
    const int CheckStatusDeny = 2; // 拒绝
    const int CheckStatusSuccess = 100; // 审核通过

    public int $cate_id = 0; // 栏目 ID
    public int $kind = 0; // 类型
    public string $title = ''; // 主题
    public string $cover = ''; // 封面
    public string $keywords = ''; // 关键字
    public string $summary = ''; // 描述
    public int $status = 0; // 状态（启用）
    public int $sort = 0; // 排序

    public int $cstatus = 1; // 审核状态（待审核）
    public string $cmessage = ''; // 不通过原历
    public int $cuser_id = 0; // 审核者 ID

    public int $user_id = 0; // 作者 ID
    public string $author = ''; // 作者
    public string $ip = ''; // IP

    public int $content_id = 0;
    public string $image_ids = '';

    public int $hot = 0; // 热门
    public int $top = 0; // 置顶
    public int $hits = 0; // 点击次数

    /**
     * @throws \Exception
     */
    public static function mapCheckStatus(int $cStatus = 0): array|string
    {
        static $data = [
            self::CheckStatusTodo => '待审',
            self::CheckStatusDeny => '不通过',
            self::CheckStatusSuccess => '通过',
        ];
        return MyData::getMapData($data,$cStatus);
    }
}