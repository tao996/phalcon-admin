<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Helper\wepay\Notify;
use App\Modules\tao\A0\open\Helper\wepay\Prepay;
use App\Modules\tao\A0\open\Helper\wepay\RefundNotify;

class WepayHelper
{

    public function prepay(string $appid, string $mchid = ''): Prepay
    {
        return new Prepay($appid, $mchid);
    }

    public function notify(string $appid, string $mchid = ''): Notify
    {
        return new Notify($appid, $mchid);
    }

    public function refundNotify(string $outTradeNo): RefundNotify
    {
        return new RefundNotify($outTradeNo);
    }
}