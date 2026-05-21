<?php

namespace App\Modules\tao\sdk\aliyun\oss;

use App\Modules\tao\sdk\OssDriverInterface;
use OSS\OssClient;

/**
 * @link https://github.com/aliyun/aliyun-oss-php-sdk
 */
class AliyunDriver implements OssDriverInterface
{

    protected string $accessKeyId = '';
    protected string $accessKeySecret = '';
    protected string $endpoint = '';
    protected string $bucket = '';
    protected string $domain = '';
    protected OssClient $ossClient;

    /**
     * @param array $config 配置信息
     */
    public function __construct(public array $config)
    {
        require_once __DIR__ . '/aliyun-oss-php-sdk-2.6.0.phar';

        $this->accessKeyId = $config['alioss_access_key_id'];
        $this->accessKeySecret = $config['alioss_access_key_secret'];
        $this->endpoint = $config['alioss_endpoint'];
        $this->bucket = $config['alioss_bucket'];
        $this->domain = $config['alioss_domain'];
        $this->ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
    }

    /**
     * 文件简单上传
     * @link https://help.aliyun.com/zh/oss/developer-reference/simple-upload
     * @param $object string  填写Object完整路径，例如 exampledir/example.txt。Object完整路径中不能包含Bucket名称。
     * @param $filePath string 填写本地文件的完整路径
     * @return string 文件访问地址
     * @throws \OSS\Core\OssException
     */
    public function uploadFile($object, $filePath): string
    {
        $upload = $this->ossClient->uploadFile($this->bucket, $object, $filePath);
        return $upload['info']['url'];
    }

    /**
     * 服务端上传
     * @link https://help.aliyun.com/zh/oss/use-cases/overview-20
     * @return array
     */
    public function serverToken(): array
    {
        $id = $this->accessKeyId;          // 请填写您的AccessKeyId。
        $key = $this->accessKeySecret;     // 请填写您的AccessKeySecret。
// $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $host = $this->endpoint;// 'http://bucket-name.oss-cn-hangzhou.aliyuncs.com';
// $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
        $callbackUrl = 'http://88.88.88.88:8888/aliyun-oss-appserver-php/php/callback.php';
        $dir = $this->config['oss_dir'];          // 用户上传文件时指定的前缀。

        $base64_callback_body = base64_encode(json_encode([
            'callbackUrl' => $callbackUrl,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        ]));

        $end = time() + 3600;//设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        // 上传策略
// https://help.aliyun.com/zh/oss/developer-reference/postobject#section-d5z-1ww-wdb
        $conditions = [
            ['content-length-range', 0, 1048576000], // 上传Object的最小和最大允许大小，单位为字节。
// 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
            ['starts-with', '$key', $dir],
            ['in', '$content-type', ['image/jpg', 'image/png', 'image/jpeg']],
        ];


        $base64_policy = base64_encode(json_encode([
            'expiration' => $this->gmt_iso8601($end),
            'conditions' => $conditions
        ]));
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        return $response;
    }

    private function gmt_iso8601($time)
    {
        return str_replace('+00:00', '.000Z', gmdate('c', $time));
    }

}