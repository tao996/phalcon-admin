<?php

namespace App\Modules\tao\Services;


use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUploadfile;
use Phax\Helper\MyUrl;
use Phax\Utils\MyData;

class UploadfileService
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    /**
     * 获取图片列表
     * @throws \Exception
     */
    public function getImages(string|array $imageIds, int $userId = 0): array
    {
        if (empty($imageIds)) {
            return [];
        }
        if (is_string($imageIds)) {
            $imageIds = explode(',', $imageIds);
            $imageIds = MyData::getInts($imageIds);
        }
        return SystemUploadfile::queryBuilder()->int('user_id', $userId)
            ->in('id', $imageIds)
            ->columns('id, url, summary')->find();
    }

    /**
     * 验证上传的图片
     * @params array|string $images 图片
     * @params int $max 最多上传数量
     * @throws \Exception
     */
    public function dbImages(array|string $images, int $max): string
    {
        if (!empty($images)) {
            if (is_string($images)) {
                $images = explode(',', $images);
            }
            if (count($images) > $max) {
                throw new \Exception('最多上传 ' . $max . ' 张图片');
            }
            $this->mvc->validate()->hostsValidate($images);
            return join(',', $images);
        } else {
            return '';
        }
    }
}