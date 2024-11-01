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

    public function addUser(int $userId)
    {
        $this->openid = $this->helper->userService()->getOpenidByUserId($this->appid, $userId);
        if (empty($this->openid)) {
            throw new \Exception('没有找到用户的 openid');
        }
    }

    public function setOpenid(string $openid)
    {
        $this->openid = $openid;
    }

    /**
     * 创建订单数据
     * @param int $amount
     * @param array $metadata
     * @return OpenOrder
     * @throws \Exception
     */
    public function createOrder(int $amount, array $metadata): OpenOrder
    {
        $this->helper->mvc->validate()->check($metadata, [
            'description|商品描述' => 'required'
        ]);
        if (empty($this->openid)) {
            throw new \Exception('user openid is empty');
        }
        if ($amount < 1) {
            throw new \Exception('订单金额不能小于1分');
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
     * @param OpenOrder $order
     * @param array{description:string,notify_url:string} $jsapiData
     * @return array
     * @throws \Exception
     */
    public function prepay(OpenOrder $order, array $jsapiData)
    {
        $this->helper->mvc->validate()->check($jsapiData, [
            'description|商品描述' => 'required',
        ]);
        if (empty($jsapiData['notify_url'])) {
            $jsapiData['notify_url'] = $this->helper->openUrlHelper()
                ->notifyDemoURL($order->appid, $order->mchid);
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
            'notify_url' => $jsapiData['notify_url']
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