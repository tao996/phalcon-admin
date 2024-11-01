<?php

namespace App\Modules\tao\A0\open\Helper\Libs;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use EasyWeChat\Pay\Message;
use Phax\Support\Logger;

/**
 * 微信支付服务（负责与微信服务器交互）
 * 此文件通常不需要测试
 */
class WepayServer
{
    public \EasyWeChat\Pay\Application $app;

    public function __construct(public MyOpenMvcHelper $helper, public string $appid, public string $mchid)
    {
        if (empty($this->appid)) {
            throw new \Exception('wechat pay helper appid is empty');
        }
        if (empty($this->mchid)) {
            throw new \Exception('wechat pay helper mchid is empty');
        }
    }

    /**
     * @throws \Exception
     */
    private function getApplication(): \EasyWeChat\Pay\Application
    {
        if (empty($this->app)) {
            $this->app = $this->helper->application()->getPay($this->appid, $this->mchid);
        }
        return $this->app;
    }

    private function checkClientResponseData(string $title, array $responseData, mixed $postData): void
    {
        if (!empty($responseData['code']) && !empty($responseData['message'])) {
            Logger::error($title, 'wechat request failed:', $postData, $responseData);
            throw new \Exception($responseData['message']);
        }
        Logger::debug($title, $postData, $responseData);
    }

    /**
     * 创建订单
     * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/mini-transfer-payment.html
     * @param array $postData
     * @return array{prepay_id:string} 需要再次调用 repay 方法
     * @throws \Exception
     */
    public function prepay(array $postData): array
    {
        $app = $this->getApplication();
        $response = $app->getClient()->postJson("/v3/pay/transactions/jsapi", $postData);
        $data = $response->toArray(false); // Array
        $this->checkClientResponseData('微信创建订单', $data, $postData);
        try {
            // 验证返回值签名
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::wrap('wechat jsapi response sign failed', $e, $data);
        }
        if (!isset($data['prepay_id'])) {
            if (isset($data['message'])) {
                throw new \Exception($data['message']);
            }
            throw new \Exception('创建订单时生成 prepayId 错误');
        }
        return $data;
//        $utils = $app->getUtils();
//        return $utils->buildBridgeConfig($data['prepay_id'], $app->getConfig()->get('app_id'));
    }

    /**
     * 订单支付回调
     * @param callable(array):void $callback
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Exception
     */
    public function notify(callable $callback)
    {
        $app = $this->getApplication();
        $server = $app->getserver();
        // https://easywechat.com/6.x/pay/index.html#签名验证
        $server->handlePaid(function (Message $message, \Closure $next) use ($callback, $app) {
            /**
             * @var array{mchid:string,appid:string,out_trade_no:string,transaction_id:string,trade_type:string,trade_state:string,trade_state_desc:string,bank_type:string,attach:string,success_time:string,payer:array{openid:string},amount:array{total:int,payer_total:int,currency:string,payer_currency:string}} $data
             */
            $data = $message->toArray();
            /* Array (
                [mchid] => 123456789
                [appid] => wx123456789
                [out_trade_no] => xxx
                [transaction_id] => xxx
                [trade_type] => JSAPI
                [trade_state] => SUCCESS
                [trade_state_desc] => 支付成功
                [bank_type] => OTHERS
                [attach] =>
                [success_time] => 2024-08-29T23:40:38+08:00
                [payer] => Array
                    (
                        [openid] => xxx
                    )

                [amount] => Array
                    (
                        [total] => 10
                        [payer_total] => 10
                        [currency] => CNY
                        [payer_currency] => CNY
                    )

            )
             */
            Logger::debug('wechatPay.notify', $data); // 记录响应信息到日志中
            try {
                // 推送消息签名验证
                $app->getValidator()->validate($app->getRequest());
                // 验证通过，其它业务
                $callback($data);
            } catch (\Exception $e) {
                Logger::error('支持通知验证失败', $e->getMessage());
            }
            return $next($message);
        });
        return $this->helper->wechatHelper()->response($server->serve());
    }

    /**
     * 退款回调
     * https://easywechat.com/6.x/pay/server.html#%E9%80%80%E6%AC%BE%E6%88%90%E5%8A%9F%E4%BA%8B%E4%BB%B6
     * @param callable $callback
     * @return \Phalcon\Http\ResponseInterface
     * @throws \Exception
     */
    public function refundNotify(callable $callback)
    {
        $app = $this->getApplication();
        $server = $app->getserver();
        $server->handleRefunded(function (Message $message, \Closure $next) use ($callback, $app) {
            $data = $message->toArray();
            Logger::debug('wechatPay.refundNotify', $data); // 记录响应日志
            try {
                $app->getValidator()->validate($app->getRequest());
                // 验证通过，其它业务
                $callback($data);
            } catch (\Exception $e) {
                Logger::error('退款回调验证失败', $e->getMessage());
            }
            // $message->out_trade_no 获取商户订单号
            // $message->payer['openid'] 获取支付者 openid
            return $next($message);
        });

// 默认返回 ['code' => 'SUCCESS', 'message' => '成功']
        return $this->helper->wechatHelper()->response($server->serve());
    }

    /**
     * 生成重新支付参数
     * @param string $prepayId
     * @return array
     */
    public function repay(string $prepayId)
    {
        $app = $this->getApplication();
        $utils = $app->getUtils();
        return $utils->buildBridgeConfig($prepayId, $app->getConfig()->get('app_id'));
    }

    /**
     * 订单查询
     * @param string $outTradeNo
     * @return mixed
     * @throws \Exception
     */
    public function queryByOutTradeNo(string $outTradeNo)
    {
        if (empty($outTradeNo)) {
            throw new \Exception('订单号查询时 out_trade_no is empty');
        }
        $app = $this->getApplication();
        $response = $app->getClient()->get("/v3/pay/transactions/out-trade-no/{$outTradeNo}", [
            'mchid' => $this->mchid,
        ]);
        $data = $response->toArray(false);
        $this->checkClientResponseData('订单商户号查询:', $data, ['out_trade_no' => $outTradeNo]);
        try {
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::wrap('wechat query by out_trade_no response sign failed', $e, $data);
        }
        return $data;
    }

    /**
     * 订单查询
     * @param string $transactionId
     * @return mixed
     * @throws \Exception
     */
    public function queryByTransactionId(string $transactionId)
    {
        if (empty($transactionId)) {
            throw new \Exception('订单号查询时 transaction_id is empty');
        }
        $app = $this->getApplication();
        $response = $app->getClient()->get("/v3/pay/transactions/id/{$transactionId}", [
            'mchid' => $this->mchid,
        ]);
        $data = $response->toArray(false);
        $this->checkClientResponseData('订单订单号查询:', $data, ['transaction_id' => $transactionId]);
        try {
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::wrap('wechat query by transaction_id response sign failed', $e, $data);
        }
        return $data;
    }

    /**
     * 退款订单查询
     * @param string $outRefundNo
     * @return mixed
     * @throws \Exception
     */
    public function refundQuery(string $outRefundNo)
    {
        if (empty($outRefundNo)) {
            throw new \Exception('退款单号查询时 out_refund_no is empty');
        }
        $app = $this->getApplication();
        $response = $app->getClient()->get("/v3/refund/domestic/refunds/{$outRefundNo}");
        $data = $response->toArray(false);
        $this->checkClientResponseData('退款单号查询:', $data, ['out_refund_no' => $outRefundNo]);
        try {
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::wrap('wechat refund query by out_refund_no response sign failed', $e, $data);
        }
        return $data;
    }

    /**
     * 退款，注意退款金额 amount 和 退款账号
     * @link https://pay.weixin.qq.com/docs/merchant/apis/mini-program-payment/create.html
     * @param array{transaction_id:string,out_trade_no:string,out_refund_no:string,reason:string,notify_url:string,amount:array{refund:int,total:int,currency:string} $postData
     * @throws \Exception
     * @return array
     */
    public function refund(array $postData)
    {
        if (empty($postData['transaction_id']) || empty($postData['out_trade_no'])) {
            throw new \Exception('退款时订单号和交易单号不能同时为空');
        }
        if (empty($postData['out_refund_no'])) {
            throw new \Exception('退款单号不能为空');
        }
        if (empty($postData['amount'])) {
            throw new \Exception('退款金额配置项为空');
        }
        if (empty($postData['amount']['refund'])) {
            throw new \Exception('退款金额不能为空');
        }
        if (empty($postData['amount']['total'])) {
            throw new \Exception('订单总金额不能为空');
        }
        if (empty($postData['notify_url'])) {
            $postData['notify_url'] = $this->helper->openUrlHelper()->refundNotifyDemoURL($postData['out_trade_no']);
        }
//        if (empty($postData['funds_account'])) {
//            $postData['funds_account'] = 'AVAILABLE';
//        }

        $app = $this->getApplication();
        $response = $app->getClient()->postJson("/v3/refund/domestic/refunds", $postData);
        $data = $response->toArray(false);
        $this->checkClientResponseData('退款申请:', $data, $postData);
        try {
            $app->getValidator()->validate($response->toPsrResponse());
        } catch (\Exception $e) {
            Logger::wrap('wechat refund response sign failed', $e, $postData, $data);
        }
        return $data;
    }

    /**
     * 关闭订单
     * @param string $outTradeNo
     * @return void
     * @throws \Exception
     */
    public function close(string $outTradeNo): void
    {
        if (empty($outTradeNo)) {
            throw new \Exception('关闭订单时订单号不能为空');
        }
        $app = $this->getApplication();
        $app->getClient()->postJson("/v3/pay/transactions/out-trade-no/{$outTradeNo}/close", [
            'mchid' => $this->mchid,
        ]);
        // Response body is empty 不需要 toArray
    }
}