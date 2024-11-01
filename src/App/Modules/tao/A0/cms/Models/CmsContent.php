<?php

namespace App\Modules\tao\A0\cms\Models;

use App\Modules\tao\BaseTaoModel;

class CmsContent extends BaseTaoModel
{
    protected string|bool $autoWriteTimestamp = false;

    /**
     * 普通内容 <br>
     * 图集：cover:'图片地址', desc:'描述性文字', link:'原始链接', from: '来源'
     * 其它格式
     * @var string
     */
    public string $content = '';


    /**
     * 获取图文列表
     * @return array
     */
    public function contentToArray():array
    {
        return json_decode($this->content, true);
    }

}