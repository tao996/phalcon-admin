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
    // 商户证书
    public string $private_key = ''; // client_key.pem 路径（随机） 商户 API 私钥
    public string $certificate = ''; // client_cert.pem 路径（随机） API 证书

    // v3 api 秘钥
    public string $secret_key = '';

    // v2 api 秘钥
    public string $v2_secret_key = '';

    // 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
    // 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader
    public string $platform_cert = ''; // 路径
    public string $remark = ''; // 备注

    // 资料是否完整
    public int $done = 0;

    // todo 安全加密（将第i位 200~500 进行加1运算）
    public int $ki = 0;
    public int $ci = 0;

    public function beforeSave()
    {
        $this->done = empty($this->private_key)
        || empty($this->certificate)
        || empty($this->platform_cert) ? 0 : 1;
    }
}