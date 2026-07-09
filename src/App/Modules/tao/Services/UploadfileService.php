<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Models\SystemUploadfile;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Validate;
use Phax\Utils\MyData;

class UploadfileService
{

    /**
     * 获取图片列表
     */
    public static function getImages(string|array $imageIds, int $userId = 0): array
    {
        if (empty($imageIds)) {
            return [];
        }
        if (is_string($imageIds)) {
            $imageIds = explode(',', $imageIds);
            $imageIds = MyData::getInts($imageIds);
        }
        return SystemUploadfile::queryBuilder()
            ->int('user_id', $userId)
            ->in('id', $imageIds)
            ->columns('id, url, summary')->find();
    }

    /**
     * 验证上传的图片
     * @params array|string $images 图片
     * @params int $max 最多上传数量
     */
    public static function dbImages(array|string $images, int $max): string
    {
        if (!empty($images)) {
            if (is_string($images)) {
                $images = explode(',', $images);
            }
            if (count($images) > $max) {
                throw new BusinessException('最多上传 ' . $max . ' 张图片');
            }
            Validate::hostsValidate($images);
            return join(',', $images);
        } else {
            return '';
        }
    }
}