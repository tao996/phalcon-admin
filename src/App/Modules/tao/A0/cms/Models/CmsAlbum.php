<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

/**
 * 图集
 */
class CmsAlbum extends BaseTaoModel
{
    use SoftDelete;

    public int $user_id = 0;
    public string $cover = '';
    public string $title = '';
    public string $summary = '';
    public string $tag = '';
    public string $image_ids = ''; //
    public int $status = 0;
    public int $sort = 0;

    public function validation()
    {
        if (empty($this->title)) {
            throw new \Exception('必须填写标题');
        }
        if (empty($this->cover)){
            throw new \Exception('必须设置封面');
        }
    }
}