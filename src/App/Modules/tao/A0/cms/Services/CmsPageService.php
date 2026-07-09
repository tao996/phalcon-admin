<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Models\CmsPage;

class CmsPageService
{

    /**
     * @param string $tag 分组或标签名
     * @param string $name 名称
     * @param int $status 状态，默认为 1
     * @return array|null
     */
    public static function findFirst(string $tag, string $name, int $status = 1): array|null
    {
        if ($page = CmsPage::queryBuilder()
            ->string('tag', $tag)
            ->string('name', $name)->int('status', $status)->findFirstArray()) {
            $page['content'] = CmsContentService::getContentById($page['content_id']);
            return $page;
        }
        return null;
    }

    public static function isRepeat(CmsPage $model): bool
    {
        $q = $model->getQueryBuilder()
            ->where(['tag' => $model->tag, 'name' => $model->name]);
        if ($model->id > 0) {
            $q->notEqual('id', $model->id);
        }
        return $q->exits();
    }
}