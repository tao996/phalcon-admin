<?php

namespace App\Modules\tao\A0\open\Helper\wepay;

use App\Modules\tao\A0\open\Logic\WepayOrderLogic;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Support\Logger;

/**
 * 支付通知
 */
class Notify extends AbstractWepay
{

    /**
     * 订单处理
     * @param array $data 通知数据
     * @param callable{OpenOrder}|null $success 订单成功修改为已支付的回调
     * @return void
     * @throws \Exception
     */
    public function handlePaid(array $data, callable $success = null): void
    {
        $order = $this->helper->orderService()->fromOutTradeNo($data['out_trade_no']);
        $logic = WepayOrderLogic::createWithOrder($this->helper, $order);
        if ($logic->payResponse($data, true)) {
            if (is_callable($success)) {
                try {
                    call_user_func($success, $order);
                } catch (\Exception $e) {
                    Logger::error('订单回调错误', [
                        'id' => $order->id,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    /**
     * @link https://easywechat.com/6.x/pay/index.html#签名验证
     * @param callable{OpenOrder}|null $success 在支持成功后接收订单数据
     * @throws \Exception
     * @return mixed 响应结果不需要再次处理直接返回给微信即可
     */
    public function response(callable $success = null): mixed
    {
        return $this->getWechatServer()->notify(function ($data) use ($success) {
            // 验证通过，其它业务
            $this->handlePaid($data, $success);
        });
    }
}