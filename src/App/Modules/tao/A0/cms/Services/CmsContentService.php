<?php

namespace App\Modules\tao\A0\cms\Services;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\cms\Models\CmsContent;


class CmsContentService
{
    public function __construct(protected MyCmsMvcHelper $cms)
    {
    }

    /**
     * 查询单页记录
     * @param int $id
     * @param bool $createIfNull 如果没有找到，是否直接创建一个模型
     * @return null|CmsContent
     */
    public function getById(int $id, bool $createIfNull = false): ?CmsContent
    {
        if ($id < 1) {
            return $createIfNull ? new CmsContent() : null;
        } else {
            $rst = CmsContent::queryBuilder()->int('id', $id)->findFirstModel();
            return $rst ?: ($createIfNull ? new CmsContent() : null);
        }
    }

    /**
     * 获取记录内容
     * @param int $id
     * @param bool $jsonDecode 是否对内容进行数组转换
     * @return string|array
     */
    public function getContentById(int $id, bool $jsonDecode = false): string|array
    {
        if ($id < 1) {
            return $jsonDecode ? [] : '';
        }
        if ($data = $this->getById($id)?->toArray()) {
            return isset($data['content']) ? ($jsonDecode ? json_decode(
                $data['content'],
                true
            ) : $data['content']) : '';
        } else {
            return $jsonDecode ? [] : '';
        }
    }

    /**
     * 用于更新 CmsContent
     * @param int $id 原 albums_id
     * @param array $albums 待保存的图集数据
     * @return CmsContent
     */
    public function saveAlbumsDataById(int $id, array $albums): CmsContent
    {
        $content = $this->filterAlbums($albums);
        $cc = $this->getById($id, true);
        $cc->content = json_encode($content);
        if (!$cc->save()) {
            throw new \Exception('保存图集错误:' . $cc->getFirstError());
        }
        return $cc;
    }

    public function filterAlbums(array $albums): array
    {
        $rows = [];
        if ($albums) {
            foreach ($albums as $album) {
                if (!empty($album['cover'])) {
                    $rows[] = [
                        'cover' => $album['cover'],
                        'desc' => $album['desc'],
                    ];
                }
            }
        }
        return $rows;
    }

    /**
     * 用于更新内容
     * @param int $id 原 content_id
     * @param string|array $content 新的内容
     * @return CmsContent
     * @throws \Exception
     */
    public function saveContentDataById(int $id, string|array $content): CmsContent
    {
        $cc = $this->getById($id, true);
        $cc->content = is_array($content) ? json_encode($content) : $content;
        if (!$cc->save()) {
            throw new \Exception('保存内容失败:' . $cc->getFirstError());
        }
        return $cc;
    }
}