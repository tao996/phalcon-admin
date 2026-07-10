<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\A0\open\ExtendControllers;

use App\Modules\tao\A0\open\Helper\wepay\Notify;
use App\Modules\tao\A0\open\Helper\wepay\Prepay;
use App\Modules\tao\A0\open\Helper\wepay\RefundNotify;
use App\Modules\tao\A0\open\Logic\WepayOrderLogic;
use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\TaoAppService;
use App\Modules\tao\utils\ResponseUtil;
use Phalcon\Di\DiInterface;
use Phalcon\Http\Request;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 简化应用集成微信支付相关操作
 * @property string $notify_path 订单支持成员回调路径，示例 `/api/p/house/order/notify/` 注意最后的 / 是必须的，因为要拼接 appid/mchid
 * @property string $refund_notify_path 退款地址路径，示例 '/api/p/house/order/notify-refund/'
 * @property Request $request
 * @property array $requestData
 * @method int getUserId()
 * @method DiInterface getDI()
 * @method array successPagination()
 * @method mixed getRequestQueryInt()
 * @method void mustPostMethod();
 * @method string getAppid()
 * @method string getMchid()
 */
trait WepayOrder
{

//    protected array|string $userActions = ['pay', 'repay', 'refund', 'query'];
//    protected array|string $openActions = ['notify', 'notifyRefund'];


    /**
     * 订单支付成功回调地址
     * @var string
     */
    private string $notify_url = '';

    /**
     * 获取下单通知
     * @param string $appid
     * @param string $mchid
     * @return string
     * @throws \Exception
     */
    public function getNotifyURL(string $appid, string $mchid): string
    {
        if (empty($this->notify_url)) {
            if (empty($this->notify_path)) {
                throw new BusinessException('both notify_url and notify_path cannot be empty');
            } elseif (!str_ends_with($this->notify_path, '/notify/')) {
                throw new BusinessException('notify_path must end with /notify/');
            }
            //  http://localhost:8071/api/p/family/order/notify/123
            $this->notify_url = TaoAppService::openUrlHelper()
                ->url($this->notify_path . $appid . '/' . $mchid);
        }
        return $this->notify_url;
    }

    public Prepay $prepay_helper;

    public function getWechatPayPrepayHelper(string $appid, string $mchid): Prepay
    {
        if (empty($this->prepay_helper)) {
            $this->prepay_helper = TaoAppService::wepayHelper()->prepay($appid, $mchid);
        }
        return $this->prepay_helper;
    }

    private string $refund_notify_url = '';

    public function getRefundNotifyURL(string $outTradeNo): string
    {
        if (empty($this->refund_notify_url)) {
            if (empty($this->refund_notify_path)) {
                throw new BusinessException('both refund_notify_url and refund_notify_path cannot be empty');
            } elseif (!str_ends_with($this->refund_notify_path, '/notify_refund/')) {
                throw new BusinessException('refund_notify_path must end with /notify_refund/');
            }
            // http://localhost:8071/api/p/family/order/notify-refund/456
            $this->refund_notify_url = TaoAppService::openUrlHelper()
                ->url($this->refund_notify_path . $outTradeNo);
        }
        return $this->refund_notify_url;
    }

    public RefundNotify $refund_notify_helper; // 退款通知

    public function getWechatPayRefundNotifyHelper(string $outTradeNo): RefundNotify
    {
        if (empty($this->refund_notify_helper)) {
            $this->refund_notify_helper = TaoAppService::wepayHelper()->refundNotify($outTradeNo);
        }
        return $this->refund_notify_helper;
    }

    public Notify $notify_helper; // 下单通知

    public function getWechatPayNotifyHelper(string $appid, string $mchid): Notify
    {
        if (empty($this->notify_helper)) {
            $this->notify_helper = TaoAppService::wepayHelper()->notify($appid, $mchid);
        }
        return $this->notify_helper;
    }

    public WepayOrderLogic $order_logic;

    /**
     * @throws \Exception
     */
    public function getOpenOrderLogic($id): WepayOrderLogic
    {
        if (empty($this->order_logic)) {
            $this->order_logic = WepayOrderLogic::createWithQB(
                $this->getOrderQueryBuilder(),
                $id
            );
        }
        return $this->order_logic;
    }

    /**
     * 可能需要重写此方法
     * @return \Phax\Db\QueryBuilder
     * @throws \Exception
     */
    protected function getOrderQueryBuilder(): \Phax\Db\QueryBuilder
    {
        return OpenOrder::queryBuilder($this->getDI())
            ->int('app', $this->getProjectId())
            ->int('user_id', $this->getUserId());
    }

    abstract protected function getProjectId(): int;

    /**
     * 下单
     * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/mini-transfer-payment.html
     * @throws \Exception
     */
    public function payAction()
    {
        $this->mustPostMethod();
        $this->payValidate();
        $appid = $this->getAppid();
        $prepayHelper = $this->getWechatPayPrepayHelper($appid, $this->getMchid());
        if ($openid = $this->request->getQuery('openid', 'string', '')) {
            $prepayHelper->setOpenid($openid);
        } else {
            $prepayHelper->addUser($this->getUserId());
        }

        $prepayHelper->setNotifyUrl($this->getNotifyURL($prepayHelper->appid, $prepayHelper->mchid));

        $order = $prepayHelper->createOrder(0, []);
        $order->user_id = $this->getUserId();
        $order->trade_type = OpenOrder::TradeTypeMini;
        $order->app = $this->getProjectId();
        $metadata = array_merge([
            'user_id' => $this->getUserId(),
        ], $this->setPayOrderData($order));

        if (empty($metadata['description'])) {
            throw new BusinessException('order.metadata description cannot be empty');
        } elseif ($order->amount < 1) {
            throw new BusinessException('order.amount cannot be less than 1');
        }
        $order->metadata = json_encode($metadata);
        $rst = $prepayHelper->prepay($order, $metadata);
        $this->prepaySuccess($order);
        return $rst;
    }

    /**
     * 验证支付数据是否正确，示例
     * @return void
     */
    abstract protected function payValidate(): void;

    /**
     * 为订单设置一些应用相关的数据，如 metadata, app
     * @param OpenOrder $openOrder
     * @return array 返回用于设置 metadata 的数据, 必须含有 description
     */
    abstract protected function setPayOrderData(OpenOrder $openOrder): array;

    abstract protected function prepaySuccess(OpenOrder $openOrder): void;

    public function notifyAction(string $appid, string $mchid)
    {
        $this->autoResponse = false;
        /**
         * @var OpenOrder $gOrder
         */
        $gOrder = null;
        try {
            $payNotifyHelper = $this->getWechatPayNotifyHelper($appid, $mchid);
            return $payNotifyHelper->response(function (OpenOrder $order) use ($gOrder) {
                $gOrder = $order;
                $this->paySuccess($gOrder);
            });
        } catch (\Exception $e) {
            Logger::error('微信支付回调处理失败', [
                'gOrder' => $gOrder?->toArray(),
                'err' => $e->getMessage(),
            ]);
            // 已经无法处理，以后使用订单查询处理
            ResponseUtil::send(['code' => 'SUCCESS', 'message' => '成功']);
            throw new BlankException();
        }
    }

    /**
     * 支付成功，通常在这里添加应用业务订单
     * @param OpenOrder $openOrder
     * @return void
     */
    abstract protected function paySuccess(OpenOrder $openOrder): void;

    /**
     * 继续支付订单
     * @throws \Exception
     */
    public function repayAction(): array
    {
        $this->mustPostMethod();
        // id 是必须的
        $id = MyData::getInt($this->requestData, 'id');
        $orderLogic = WepayOrderLogic::createWithQB(
            $this->getOrderQueryBuilder(),
            $id
        );
        return $orderLogic->continuePay();
    }

    /**
     * 订单查询
     * @throws \Exception
     */
    public function queryAction(): array
    {
        $this->mustPostMethod();
        $id = MyData::getInt($this->requestData, 'id');
        $orderLogic = WepayOrderLogic::createWithQB(
            $this->getOrderQueryBuilder(),
            $id
        );
        $order = $orderLogic->getOrder();
        if ($responseData = $orderLogic->query()) {
            if ($order->isRefunding()) {
                // 处理 OpenOrder 订单退款数据
                $orderLogic->refundResponse($responseData);
            } else {
                $orderLogic->payResponse($responseData, true);
            }
        }
        if ($order->isPaySuccess()) {
            $this->paySuccess($order);
        } elseif ($order->isRefundSuccess()) {
            $this->refundSuccess($order);
        }
        return $this->queryActionResponse($order);
    }

    /**
     * 退款成功业务处理逻辑
     * @param OpenOrder $openOrder
     * @return void
     */
    abstract protected function refundSuccess(OpenOrder $openOrder): void;

    protected function queryActionResponse(OpenOrder $openOrder): array
    {
        $rst = $openOrder->toArray();
        return MyData::picker($rst, [
                'id',
                'created_at',
                'user_id',
                'amount',
                'metadata',
                'success_time',
                'status',
                'refund_at',
                'refund_amount',
                'refund_time'
            ]
        );
    }

    /**
     * 退款
     * @throws \Exception
     */
    public function refundAction(): array
    {
        $this->mustPostMethod();

        $id = MyData::getInt($this->requestData, 'id');
        $orderLogic = $this->getOpenOrderLogic($id);
        $orderLogic->mustAllowRefund();
        $order = $orderLogic->getOrder();
        if ($order->isRefunding()) {
            throw new BusinessException('订单正在退款处理中');
        }
        $this->refundOrderValidate($order);
        if ($order->refund_amt === 0) {
            $order->refund_amt = MyData::getInt($this->requestData, 'amt');
        }
        if ($order->refund_amt === 0) {
            $order->refund_amt = $order->amount;
        } elseif ($order->refund_amt < 1) {
            throw new BusinessException('退款金额不能为空');
        } elseif ($order->refund_amt > $order->amount) {
            throw new BusinessException('退款金额不能大于订单金额');
        }

        $responseData = $orderLogic->refund(function () use ($order) {
            return [
                'transaction_id' => $order->transaction_id,
                'out_trade_no' => $order->getOutTradeNo(),
                'out_refund_no' => $order->getOutRefundNo(),
                'amount' => [
                    'refund' => $order->refund_amt,
                    'total' => $order->amount,
                    'currency' => OpenOrder::getCurrencyText($order->currency),
                ],
                'notify_url' => $this->getRefundNotifyURL($order->getOutTradeNo()),
            ];
        });
        // 处理订单退款
        if ($orderLogic->refundResponse($responseData)) {
            if ($responseData['status'] == 'SUCCESS') {
                $this->refundSuccess($orderLogic->getOrder());
            }
        }
        return $this->refundActionResponse($orderLogic->getOrder());
    }

    /**
     * 退款订单处理验证
     * @param OpenOrder $openOrder
     * @return void
     */
    abstract protected function refundOrderValidate(OpenOrder $openOrder): void;

    protected function refundActionResponse(OpenOrder $openOrder): array
    {
        return $this->queryActionResponse($openOrder);
    }

    /**
     * 退款通知
     * @throws \Exception
     */
    public function notifyRefundAction(string $outTradeNo = '')
    {
        $this->autoResponse = false;
        if (IS_DEBUG) {
            Logger::debug('接收退款通知:' . $outTradeNo);
        }
        $helper = $this->getWechatPayRefundNotifyHelper($outTradeNo);
        return $helper->response(function (OpenOrder $order) {
            $this->refundSuccess($order);
        });
    }

    /**
     * 关闭订单
     * @throws \Exception
     */
    public function closeAction(): array
    {
        $this->mustPostMethod();
        $id = MyData::getInt($this->requestData, 'id');
        $orderLogic = WepayOrderLogic::createWithQB(
            $this->getOrderQueryBuilder(),
            $id
        );
        $order = $orderLogic->getOrder();
        if (in_array($order->status, [OpenOrder::StatusCreate, OpenOrder::StatusNotPay])) {
            $orderLogic->close();
            $orderLogic->closeResponse();
            $this->closeSuccess($order);
            return $this->closeActionResponse($order);
        }
        throw new LogException('订单状态错误，无法关闭', [
            'order' => $order->toArray()
        ]);
    }

    /**
     * 成功关闭订单之后的业务处理逻辑
     * @param OpenOrder $openOrder
     * @return void
     */
    abstract protected function closeSuccess(OpenOrder $openOrder): void;

    protected function closeActionResponse(OpenOrder $openOrder): array
    {
        return $this->queryActionResponse($openOrder);
    }
}