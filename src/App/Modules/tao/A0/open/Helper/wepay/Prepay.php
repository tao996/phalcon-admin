<?php

namespace App\Modules\tao\A0\open\Helper\wepay;

use App\Modules\tao\A0\open\Models\OpenOrder;

/**
 * 微信下单服务
 * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/mini-prepay.html
 */
class Prepay extends AbstractWepay
{
    public string $openid = '';
    public OpenOrder $order;
    public string $notify_url = '';

    public function addUser(int $userId): static
    {
        $this->openid = $this->helper->userService()->getOpenidByUserId($this->appid, $userId);
        if (empty($this->openid)) {
            throw new \Exception('没有找到用户的 openid');
        }
        return $this;
    }

    public function setOpenid(string $openid): static
    {
        $this->openid = $openid;
        return $this;
    }

    public function setNotifyUrl(string $notify_url): static
    {
        $this->notify_url = $notify_url;
        return $this;
    }

    /**
     * 创建订单数据（订单未保存）
     * @param int $amount
     * @param array $metadata
     * @return OpenOrder
     * @throws \Exception
     */
    public function createOrder(int $amount, array $metadata): OpenOrder
    {
        if (empty($this->openid)) {
            throw new \Exception('user openid is empty');
        }
        $order = new OpenOrder();
        $order->appid = $this->appid;
        $order->channel = OpenOrder::ChannelWepay;
        $order->trade_type = OpenOrder::TradeTypeMini;
        $order->mchid = $this->mchid;
        $order->openid = $this->openid;
        $order->amount = $amount;
        $order->metadata = json_encode($metadata);
        return $order;
    }


    /**
     * 生成支付参数
     * @param OpenOrder $order
     * @param array{description:string,notify_url?:string} $jsapiData
     * @return array {appid:string,timeStamp:string,nonceStr:string,package:string,signType:string,paySign:string}
     * @throws \Exception
     */
    public function prepay(OpenOrder $order, array $jsapiData, bool $demo = false): array
    {
        $this->helper->mvc->validate()->check($jsapiData, [
            'description|商品描述' => 'required',
        ]);
        $notify_url = $this->notify_url;
        if (empty($notify_url)) {
            if (!empty($jsapiData['notify_url'])) {
                $notify_url = $jsapiData['notify_url'];
            } elseif ($demo) {
                $notify_url = $this->helper->openUrlHelper()
                    ->notifyDemoURL($order->appid, $order->mchid);
            }
        }
        if (empty($notify_url)) {
            throw new \Exception('notify_url 不能为空');
        }
        if ($order->amount < 1) {
            throw new \Exception('订单金额不能小于1分');
        }
        // 创建订单
        if (!$order->create()) {
            throw new \Exception($order->getFirstError());
        }

        $postData = [
            'appid' => $order->appid,
            'mchid' => $order->mchid,
            'out_trade_no' => $order->getOutTradeNo(),
            'amount' => [
                'total' => $order->amount,
                'currency' => OpenOrder::CURRENCY[$order->currency] ?? 'CNY',
            ],
            'payer' => [
                'openid' => $order->openid
            ],
            'description' => $jsapiData['description'],
            'notify_url' => $notify_url
        ];

        $wepayServer = $this->getWechatServer();
        $data = $wepayServer->prepay($postData);
        $order->response = json_encode($data);
        if (!$order->save()) {
            throw new \Exception($order->getFirstError());
        }
        $this->order = $order;
        return $wepayServer->repay($data['prepay_id']);
    }

}