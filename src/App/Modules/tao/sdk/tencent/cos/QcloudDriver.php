<?php

namespace App\Modules\tao\sdk\tencent\cos;

use App\Modules\tao\sdk\OssDriverInterface;
use Qcloud\Cos\Client;

/**
 * @link https://github.com/tencentyun/cos-php-sdk-v5
 */
class QcloudDriver implements OssDriverInterface
{
    protected string $secretId = '';

    protected string $secretKey = '';

    protected string $region = '';

    protected string $bucket = '';

    protected string $schema = 'https';

    protected $cosClient;

    public function __construct($config)
    {
        require_once __DIR__ . '/qcloud-cos-sdk-v5-7.phar';
        $this->secretId = $config['txcos_secret_id'];
        $this->secretKey = $config['txcos_secret_key'];
        $this->region = $config['txcos_region'];
        $this->bucket = $config['txcos_bucket'];
        $this->schema = $config['schema'];
        $this->cosClient = new Client(
            [
                'region' => $this->region,
                'schema' => $this->schema,
                'credentials' => [
                    'secretId' => $this->secretId,
                    'secretKey' => $this->secretKey],
            ]);
    }

    /**
     * @link https://cloud.tencent.com/document/product/436/12266
     * @param string $objectName
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    public function uploadFile(string $objectName, string $filePath)
    {
        $key = $objectName;
        $file = fopen($filePath, "rb");
        if ($file) {
            $result = $this->cosClient->putObject([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'Body' => $file]
            );

            $result = (array)$result;
            $result = $result[' * data'];
            if (!isset($result['Location'])) {
                throw new \Exception('腾讯云保存失败');
            }
            return $this->schema . '://' . $result['Location'];
        } else {
            throw new \Exception('无法读取本地文件信息有误');
        }
    }
}