<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\cms\Models\CmsAd;

class CmsAdService
{
    public function __construct(protected MyCmsMvcHelper $cms)
    {
    }

    public function indexList()
    {
        return CmsAd::queryBuilder()
            ->int('at_index', 1)
            ->int('status', 1)
            ->and(CmsAd::activeCondition(time()), true)
            ->orderBy('sort desc, id desc')
            ->findColumn(['id', 'cover', 'title', 'link', 'kind', 'tag']);
    }
}