<?php

namespace App\Modules\tao\A0\open\Helper\wepay;

use App\Modules\tao\A0\open\Helper\Libs\WepayServer;
use App\Modules\tao\A0\open\Logic\WepayOrderLogic;
use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\A0\open\Service\OpenOrderService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Support\Logger;

/**
 * 退款通知
 */
class RefundNotify
{
    private OpenOrder $order;
    public WepayServer $wepayServer;

    public function __construct(string $outTradeNo)
    {
        if (empty($outTradeNo)) {
            throw new BusinessException('wechat refund notify outTradeNo is empty');
        }
        $this->order = OpenOrderService::fromOutTradeNo($outTradeNo);
    }

    public function getOrder(): OpenOrder
    {
        return $this->order;
    }

    public function getWechatServer(): WepayServer
    {
        if (empty($this->wepayServer)) {
            $this->wepayServer = new WepayServer($this->order->appid, $this->order->mchid);
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
        $logic = WepayOrderLogic::createWithOrder($this->order);
        if ($logic->refundResponse($data)) {
            if (is_callable($success)) {
                try {
                    call_user_func($success, $this->order);
                } catch (\Exception $e) {
                    throw new LogException('退款回调处理错误', [
                        'data' => $data,
                        'order' => $this->order->toArray(),
                    ], previous: $e);
                }
            } elseif (IS_DEBUG) {
                Logger::debug('不需要处理退款 handleRefund', $data);
            }
        } elseif (IS_DEBUG) {
            Logger::debug('不需要处理已退款的订单', $data);
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