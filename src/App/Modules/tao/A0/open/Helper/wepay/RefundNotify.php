<?php

namespace App\Modules\tao\A0\open\Helper\wepay;

use App\Modules\tao\A0\open\Helper\Libs\WepayServer;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Logic\WepayOrderLogic;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Logger;

/**
 * 退款通知
 */
class RefundNotify
{
    private OpenOrder $order;
    public WepayServer $wepayServer;

    public function __construct(public MyOpenMvcHelper $helper, string $outTradeNo)
    {
        if (empty($outTradeNo)) {
            throw new BusinessException('wechat refund notify outTradeNo is empty');
        }
        $this->order = $this->helper->orderService()->fromOutTradeNo($outTradeNo);
    }

    public function getOrder(): OpenOrder
    {
        return $this->order;
    }

    public function getWechatServer(): WepayServer
    {
        if (empty($this->wepayServer)) {
            $this->wepayServer = new WepayServer($this->helper, $this->order->appid, $this->order->mchid);
        }
        return $this->wepayServer;
    }

    /**
     * 退款订单处理
     * @param array $data
     * @param callable{OpenOrder}|null $success 订单成功修改为已退款的回调
     * @return void
     */
    public function handleRefund(array $data, callable $success = null): void
    {
        $logic = WepayOrderLogic::createWithOrder($this->helper, $this->order);
        if ($logic->refundResponse($data)) {
            if (is_callable($success)) {
                try {
                    if (IS_DEBUG){
                        Logger::info('准备处理退款业务逻辑');
                    }
                    call_user_func($success, $this->order);
                } catch (\Exception $e) {
                    Logger::error('退款回调错误', [
                        'id' => $this->order->id,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        } else {
            if (IS_DEBUG){
                Logger::info('不需要处理退款 handleRefund');
            }
        }
    }

    /**
     * @param callable|null $callback
     * @return mixed
     * @throws \Exception
     */
    public function response(callable $callback = null): mixed
    {
        return $this->getWechatServer()->refundNotify(function ($data) use ($callback) {
            $this->handleRefund($data, $callback);
        });
    }
}