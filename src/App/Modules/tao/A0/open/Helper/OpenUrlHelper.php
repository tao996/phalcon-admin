<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Service\OpenConfigService;
use Phax\Foundation\AppService;

readonly class  OpenUrlHelper
{
    /**
     * 代理地址，通常用于生成外网访问地址
     * @var string|int|mixed
     */
    public string $origin;

    public function __construct()
    {
        $this->origin = OpenConfigService::getWith('proxy_origin',
            AppService::context()->appOrigin());
    }

    /**
     * 拼接一个 模块 URL 地址
     * @param string $path 路径地址,示例 'tao.wechat/auth'
     * @param array $query 查询参数
     * @return string
     */
    public function moduleUrl(string $path, array $query = []): string
    {
        return AppService::url([
            'path' => $path,
            'query' => $query,
            'module' => true,
            'origin' => $this->origin,
        ]);
    }

    /**
     * 拼接一个地址
     * @param string $path
     * @param array $query
     * @return string
     */
    public function url(string $path, array $query = []): string
    {
        return AppService::url([
            'path' => $path,
            'query' => $query,
            'origin' => $this->origin,
        ]);
    }

    public function notifyDemoURL(string $appid, string $mchid): string
    {
        return $this->moduleUrl('tao.open/demo.pay/notify/' . $appid . '/' . $mchid);
    }

    public function refundNotifyDemoURL(string $outTradeNo): string
    {
        return $this->moduleUrl('tao.open/demo.pay/refund-notify/' . $outTradeNo);
    }
}