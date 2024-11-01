<?php

namespace App\Modules\tao\A0\open\Service;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Models\OpenOrder;

class OpenOrderService
{
    public function __construct(public MyOpenMvcHelper $helper)
    {
    }
    /**
     * 通过订单号查询订单
     * @param string $outTradeNo
     * @return OpenOrder
     * @throws \Exception
     */
    public function fromOutTradeNo(string $outTradeNo): OpenOrder
    {
        $data = explode('_', $outTradeNo);
        if (count($data) != 3) {
            throw new \Exception('不符合规划的订单号');
        } elseif (intval($data[0]) < 1) {
            throw new \Exception('订单 ID 错误');
        }

        $order = OpenOrder::findFirst($data[0]);
        if (empty($order)) {
            throw new \Exception('没有找到符合订单号的记录');
        } elseif ($order->created_at != $data[1]) {
            throw new \Exception('订单号数据错误 1');
        } elseif ($order->rndcode != $data[2]) {
            throw new \Exception('订单号数据错误 2');
        }

        return $order;
    }

    public function mustExits(int $orderId, int $userId): void
    {
        if ($orderId < 1 || $userId < 1) {
            throw new \Exception('检查订单时参数错误');
        }
        if (OpenOrder::queryBuilder()
            ->int('id', $orderId)
            ->int('user_id', $userId)
            ->notExists()) {
            throw new \Exception('订单不存在或没有权限查看');
        }
    }


}