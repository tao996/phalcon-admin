<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseTaoModel;

/**
 * 微信支付应用配置
 * @link [easywechat 支付文档](https://easywechat.com/6.x/pay/index.html)
 * @link [微信支付证书](https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=4_3)
 */
class OpenMch extends BaseTaoModel
{
    public string $mchid = ''; // 商户号 ID

    /**
     * 商户证书
     * @var string client_key.pem 路径（随机） 商户 API 私钥
     */
    public string $private_key = '';

    /**
     * 商户证书
     * @var string client_cert.pem 路径（随机） API 证书
     */
    public string $certificate = '';

    // v3 api 秘钥
    public string $secret_key = '';

    /**
     * @deprecated
     * @var string v2 api 秘钥
     */
    public string $v2_secret_key = '';

    /**
     * @var string 微信支付公钥ID
     */
    public string $pubkey_id = '';
    /**
     * @var string 微信支付公钥文件
     */
    public string $pubkey = '';

    /**
     * @deprecated
     * @var string 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
     * 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader
     */
    public string $platform_cert = ''; // 路径
    public string $remark = ''; // 备注

    // 资料是否完整
    public int $done = 0;

    public function beforeSave(): void
    {
        $this->done = empty($this->private_key)
        || empty($this->pubkey)
        || empty($this->platform_cert) ? 0 : 1;
    }
}