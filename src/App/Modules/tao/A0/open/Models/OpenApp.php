<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\A0\open\Data\Config;
use App\Modules\tao\BaseTaoModel;
use Phax\Traits\SoftDelete;

class OpenApp extends BaseTaoModel
{
    use SoftDelete;

    public int $sort = 0;
    public string $title = ''; // 应用名称
    public int $platform = 0; // 平台
    public string $kind = ''; // 应用类型 dyh 订阅号/fwh 服务号/web 网页/mini 小程序/work 企业

    public string $appid = ''; // appId 或者企业应用 agentId
    public string $secret = '';// 密钥

    public string $crop_id = '';  // 企业微信 cropId
    public string $token = ''; // 令牌
    public string $enc_method = ''; // 加密方式
    public string $aes_key = ''; // 消息加密密钥
    public int $online = 1; // 线上版本


    /**
     * 签名算法（交易系统）
     * @link https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/server/signature-algorithm
     */
    public string $public_key = ''; // 平台公钥
    public int $pi0 = 0;
    public string $rsa_public_key = ''; // 应用公钥
    public int $pi1 = 0;
    public string $rsa_private_key = ''; // 应用私钥
    public int $pi2 = 0;
    public int $done = 0; // 证书资料是否完整（交易系统）
    public int $sandbox = 0; // 是否沙盒

    public int $status = 1; // 状态
    public string $remark = ''; // 备注

    public function beforeValidation()
    {
        if (empty($this->appid)) {
            throw new \Exception('appid 不能为空');
        }
        if (empty($this->secret)) {
            throw new \Exception('secret 不能为空');
        }
        if (!in_array($this->kind, array_keys(Config::MapKinds))) {
            throw new \Exception('不支持的抖音应用类型');
        }
        if (self::queryBuilder()->where('appid', $this->appid)
            ->notEqual('id', $this->id)->exits()
        ) {
            throw new \Exception('重复的 appid');
        }
        switch ($this->platform) {
            case Config::Tiktok;
                break;
            case Config::Wechat;
                if ('work' == $this->kind && empty($this->crop_id)) {
                    throw new \Exception('企业微信必须填写 cropId');
                }
                break;
            default:
                throw new \Exception('不支持的平台');
        }
    }

    public function beforeSave()
    {
        if (empty($this->public_key)) {
            $this->pi0 = 0;
        }
        if (empty($this->rsa_public_key)) {
            $this->pi1 = 0;
        }
        if (empty($this->rsa_private_key)) {
            $this->pi2 = 0;
        }
        $this->done = empty($this->public_key)
        || empty($this->rsa_public_key)
        || empty($this->rsa_private_key) ? 0 : 1;
    }
}