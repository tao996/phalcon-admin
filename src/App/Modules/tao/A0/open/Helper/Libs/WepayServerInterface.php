<?php

namespace App\Modules\tao\A0\open\Helper\Libs;

/**
 * 微信支付服务接口（用于测试注入 mock）
 * @see WepayServer 生产实现
 */
interface WepayServerInterface
{
    public function prepay(array $postData): array;

    public function repay(string $prepayId);

    public function notify(callable $callback);

    public function refundNotify(callable $callback);

    public function queryByOutTradeNo(string $outTradeNo);

    public function queryByTransactionId(string $transactionId);

    public function refundQuery(string $outRefundNo);

    public function refund(array $postData);

    public function close(string $outTradeNo): void;
}
