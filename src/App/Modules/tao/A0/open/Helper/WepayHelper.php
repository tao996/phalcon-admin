<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\A0\open\Helper\wepay\Notify;
use App\Modules\tao\A0\open\Helper\wepay\Prepay;
use App\Modules\tao\A0\open\Helper\wepay\RefundNotify;

class WepayHelper
{
    public function __construct(public MyOpenMvcHelper $helper)
    {
    }

    public function prepay(string $appid, string $mchid = ''): Prepay
    {
        return new Prepay($this->helper, $appid, $mchid);
    }

    public function notify(string $appid, string $mchid = ''): Notify
    {
        return new Notify($this->helper, $appid, $mchid);
    }

    public function refundNotify(string $outTradeNo): RefundNotify
    {
        return new RefundNotify($this->helper, $outTradeNo);
    }
}