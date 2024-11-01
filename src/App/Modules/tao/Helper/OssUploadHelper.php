<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\qiniu\QiniuDriver;

class OssUploadHelper
{
    public const array TypeMap = [
        'local' => '本地存储',
        'qnoss' => '七牛云 oss',
        'alioss' => '阿里云 oss',
        'txcos' => '腾讯云 cos'
    ];
    public function __construct(protected MyMvcHelper $helper)
    {
    }

    /**
     * 文件上传的 token
     * https://developer.qiniu.com/kodo/manual/put-policy
     * @return QiniuDriver
     * @throws \Exception
     */
    public function qiniu(): QiniuDriver
    {
        return new QiniuDriver($this->helper->configService()->uploadConfig());
    }

    /**
     * 前端上传文件时，必须使用指定的前缀
     * @param string $appid
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function qiniuPrefix(string $appid, int $userId, array $options = []): array
    {
        $qiniu = $this->qiniu();
        // 必须以 appid/yyyymmdd_userid_ 为前缀
        $filePrefix = $appid . '/' . date('Ym') . '/' . $userId . '_';
        $token = $qiniu->getAuth()->uploadToken(
            $qiniu->getBucket() . ':' . $filePrefix,
            null,
            3600,
            array_merge([
                'isPrefixalScope' => 1,
                'mimeLimit' => 'image/*',
                'fsizeLimit' => 1024 * 1024 * 10,
            ], $options)
        );
        return [
            'key' => false,
            'prefix' => true,
            'user_id' => $userId,
            'token' => $token,
            'expire' => time() + 3600,
            'domain' => $qiniu->getDomain(),
        ];
    }

    /**
     * 强制上传后保存为指定的文件名（忽略客户端指定的 key）
     * @param string $appid
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function qiniuKey(string $appid, int $userId, array $options = [])
    {
        $qiniu = $this->qiniu();
        $token = $qiniu->getAuth()->uploadToken(
            $qiniu->getBucket(),
            null,
            3600,
            array_merge([
                'mimeLimit' => 'image/*',
                'fsizeLimit' => 1024 * 1024 * 10, // 10M
                'forceSaveKey' => true, // 自定义资源名称
                'saveKey' => $appid . '/' . date('Ym') . '/' . $userId . '_' . time() . '_' . rand(1, 100000000),
            ], $options)
        );

        return [
            'key' => true,
            'prefix' => false,
            'user_id' => $userId,
            'token' => $token,
            'expire' => time() + 3600,
            'domain' => $qiniu->getDomain(),
        ];
    }
}