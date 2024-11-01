<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\cms\Models\CmsPage;

class CmsPageService
{
    public function __construct(protected MyCmsMvcHelper $cms)
    {
    }

    /**
     * @param string $tag 分组或标签名
     * @param string $name 名称
     * @param int $status 状态，默认为 1
     * @return array|null
     */
    public function findFirst(string $tag, string $name, int $status = 1): array|null
    {
        if ($page = CmsPage::queryBuilder()->string('tag', $tag)
            ->string('name', $name)->int('status', $status)->findFirstArray()) {
            $page['content'] = $this->cms->contentService()->getContentById($page['content_id']);
            return $page;
        }
        return null;
    }
}