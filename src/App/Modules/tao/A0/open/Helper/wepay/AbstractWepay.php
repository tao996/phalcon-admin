<?php

namespace App\Modules\tao\A0\open\Helper\wepay;

use App\Modules\tao\A0\open\Helper\Libs\WepayServer;
use App\Modules\tao\A0\open\Helper\Libs\WepayServerInterface;
use App\Modules\tao\A0\open\Service\OpenMchService;
use Phax\Support\Exception\BusinessException;

abstract class AbstractWepay
{
    public WepayServerInterface $wepayServer;

    /**
     * @param string $appid 此处的 appid 为微信支付的 appid
     * @param string $mchid 微信支付商户号，如果不填写，则使用默认的商户号
     */
    public function __construct(public string $appid, public string $mchid = '')
    {
        if (empty($appid)) {
            throw new BusinessException('wepay appid is empty');
        }

        if (empty($this->mchid)) {
            $this->mchid = OpenMchService::getDefaultMchid();
        }
    }

    /**
     * 注入 mock server（测试用）
     */
    public function setWechatServer(WepayServerInterface $server): void
    {
        $this->wepayServer = $server;
    }

    /**
     * @throws \Exception
     */
    public function getWechatServer(): WepayServerInterface
    {
        if (empty($this->wepayServer)) {
            $this->wepayServer = new WepayServer($this->appid, $this->mchid);
        }
        return $this->wepayServer;
    }
}