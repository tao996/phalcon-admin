<?php

namespace App\Modules\tao\A0\open\Logic;

use App\Modules\tao\A0\open\Helper\Libs\WepayServer;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Db\QueryBuilder;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 负责处理订单的业务逻辑
 */
class WepayOrderLogic
{
    public OpenOrder $order;
    public WepayServer $helper;

    protected function __construct(public MyOpenMvcHelper $mvc)
    {
    }

    public static function createWithQB(MyOpenMvcHelper $mvc, QueryBuilder $openOrderQb, int $orderId): WepayOrderLogic
    {
        if ($orderId < 1) {
            throw new \Exception('订单ID不能为空');
        }
        $logic = new WepayOrderLogic($mvc);
        $logic->order = $openOrderQb->int('id', $orderId)->findFirstModel();
        if (empty($logic->order)) {
            throw new \Exception('没有找到符合条件的订单');
        }
        return $logic;
    }

    public static function createWithOrder(MyOpenMvcHelper $mvc, OpenOrder $order): WepayOrderLogic
    {
        if (empty($order) || $order->id < 1) {
            throw new \Exception('订单为空或数据错误');
        }
        $logic = new WepayOrderLogic($mvc);
        $logic->order = $order;
        return $logic;
    }

    /**
     * @throws \Exception
     */
    public function getWechatPayHelper(): WepayServer
    {
        if (empty($this->helper)) {
            $this->helper = new WepayServer($this->mvc, $this->order->appid, $this->order->mchid);
        }
        return $this->helper;
    }

    public function getOrder(): OpenOrder
    {
        return $this->order;
    }

    /**
     * @throws \Exception
     */
    public function continuePay(): array
    {

        if (!$this->order->continuePayStatus()) {
            throw new \Exception('不是待支付的订单');
        }
        $prepayId = '';
        if (!empty($this->order->response)) {
            $prepayId = json_decode($this->order->response, true)['prepay_id'] ?? '';
        }
        if (empty($prepayId)) {
            $this->order->status = OpenOrder::StatusDiscard;
            $this->order->save();
            throw new \Exception('订单支付信息丢失，无法继续支付');
        }
        return $this->getWechatPayHelper()->repay($prepayId);
    }

    /**
     * 订单查询
     * @return false|array false 不处理业务逻辑
     * @throws \Exception
     */
    public function query(): false|array
    {
        if ($this->order->isPaySuccess() || $this->order->isRefundSuccess()) {
            return false;
        }

        $helper = $this->getWechatPayHelper();
        if ($this->order->isRefunding()) {
            return $this->refundQuery();
        } else {
            if ($this->order->transaction_id) {
                $data = $helper->queryByTransactionId($this->order->transaction_id);
            } else {
                $data = $helper->queryByOutTradeNo($this->order->out_trade_no);
            }
            return $data;
        }
    }

    /**
     * 处理查询返回数据
     * @param array $data
     * @param bool $saveChange 是否更新订单数据
     * @return bool
     * @throws \Exception
     */
    public function payResponse(array $data, bool $saveChange = false): bool
    {
        if ($this->order->success_time > 0) {
            return false; // 已经成功支付
        }
//        Logger::debug('订单[' . $this->order->id . ']查询结果', $data);
        if (isset($data['trade_state']) && $data['trade_state'] == 'SUCCESS') {
            // 当支付成功时,订单查询与支付通知的结果基本相同
            /*
            [mchid] => xx
            [appid] => xx
            [out_trade_no] => xx
            [transaction_id] => xx
            [trade_type] => JSAPI
            [trade_state] => SUCCESS
            [trade_state_desc] => 支付成功
            [bank_type] => OTHERS
            [attach] =>
            [success_time] => 2024-08-29T20:45:55+08:00
            [payer] => Array(
                    [openid] => xx
                )
            [amount] => Array(
                    [total] => 10 [payer_total] => 10
                    [currency] => CNY [payer_currency] => CNY
                )

             */
            $this->order->success_time = strtotime($data['success_time']);
            $this->order->transaction_id = $data['transaction_id'];
            $this->order->status = OpenOrder::StatusSuccess;
            if ($saveChange) {
                if (!$this->order->updateColumns(['status', 'transaction_id', 'success_time'])) {
                    Logger::info('更新订单支持结果数据错误', $this->order->getFirstError(), $this->order->toArray());
                }
            }
            return true;
        } else {
            Logger::info('payResponse 订单状态待处理：', $data, $this->order->toArray());
        }
        return false;
    }

    /**
     * 必须允许退款
     * @return void
     * @throws \Exception
     */
    public function mustAllowRefund(): void
    {
        if ($this->order->isRefundSuccess()) {
            throw new \Exception('当前订单已经退款成功');
        }
        if (!$this->order->isPaySuccess()) {
            throw new \Exception('只有支付成功的订单才能退款');
        }
        if ($this->order->success_time + 365 * 3600 * 24 < time()) {
            throw new \Exception('订单超过1年，无法退款');
        }
    }

    /**
     * 退款申请
     * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/create.html
     * @param callable{array} $setPostRefundData
     * @return array{refund_id:string,out_refund_no:string,transation_id:string,out_trade_no:string,channel:string,use_received_account:string,success_time:string,create_time:string,status:string,funds_account:string,amount:array{total:int,refund:int}}
     * @throws \Exception
     */
    public function refund(callable $setPostRefundData): array
    {
        if ($this->order->isRefunding()) {
//            dd('正在退款中',$this->order->toArray());
            throw new \Exception('当前订单正在退款中');
        }
        // 申请退款
        $refundData = $setPostRefundData();
        return $this->getWechatPayHelper()
            ->refund($refundData);
    }

    /**
     * @throws \Exception
     */
    public function refundQuery(): array
    {
        return $this->getWechatPayHelper()
            ->refundQuery($this->order->out_refund_no);
    }

    /**
     * 处理退款返回数据
     * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/create.html
     * @param array $data
     * @return bool
     */
    public function refundResponse(array $data, bool $saveChange = false): bool
    {
        Logger::debug('订单[' . $this->order->id . ']退款结果', $data);
        if ($this->order->refund_time > 0) { // 已经处理过退款
            return false;
        }
        if (isset($data['status'])) {
            // 修改 OpenOrder
            if ($this->order->refund_at < 1) {
                $this->order->refund_at = time();
            }
            if ($data['status'] == 'SUCCESS') {
                $this->order->refund_id = $data['refund_id'];
                $this->order->refund_time = strtotime($data['success_time']);
                $this->order->refund_status = OpenOrder::RefundStatusSuccess;
                $this->order->refund_amount = $data['amount']['refund'];
                if ($saveChange) {
                    if (!$this->order->updateColumns(
                        ['refund_at', 'refund_id', 'refund_time', 'refund_status', 'refund_amount']
                    )) {
                        Logger::info(
                            '更新订单退款结果数据错误',
                            $this->order->getFirstError(),
                            $this->order->toArray()
                        );
                    }
                }
            } else {
                $this->order->refund_status = MyData::getMapData(OpenOrder::MapText2RefundStatus, $data['status'], 0);
                if ($saveChange) {
                    if (!$this->order->updateColumns(['refund_at', 'refund_status'])) {
                        Logger::info(
                            '更新订单退款结果数据错误',
                            $this->order->getFirstError(),
                            $this->order->toArray()
                        );
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 关闭订单
     * @return void
     * @throws \Exception
     */
    public function close()
    {
        $this->getWechatPayHelper()->close($this->order->getOutTradeNo());
    }

    /**
     * @throws \Exception
     */
    public function closeResponse()
    {
        $this->order->status = OpenOrder::StatusClose;
        if (!$this->order->save()) {
            Logger::wrap('更新订单关闭状态失败', null, $this->order->getFirstError());
        }
    }

}