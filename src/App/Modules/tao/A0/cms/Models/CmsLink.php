<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;

/**
 * 友情链接
 */
class CmsLink extends BaseTaoModel
{
    public string $title = '';
    public string $href = '';
    public int $status = 0;
    public int $sort = 0;
    public int $tag = 0;

    public function beforeSave()
    {
        if (empty($this->title)){
            throw new \Exception('必须填写链接标题');
        }
        if (empty($this->href)){
            throw new \Exception('必须填写链接地址');
        }
    }
}