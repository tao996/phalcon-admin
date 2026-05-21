<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseTaoModel;
use Phax\Support\Facade\MyHelperFacade;

/**
 * 订单表（当前模型只支持一单一退）
 * 如果需要支持多次退款，则需要创建退款单表 OrderRefund
 * 如果需要关联多种商品（SKU），则需要商品表 OrderProduct
 */
class OpenOrder extends BaseTaoModel
{
    public const int  StatusCreate = 1; // 待支付
    public const int  StatusNotPay = 5; // 未支付（待支付的订单查询时即为 NotPay）
    public const int  StatusRevoked = 10;// 已撤销（仅付款码支付会返回）
    public const int  StatusUserPaying = 11;//用户支付中（仅付款码支付会返回）
    public const int  StatusSuccess = 20; // 支付成功
    public const int  StatusError = 40; // 支付失败（仅付款码支付会返回）（其它原因，如银行返回失败）
    public const int  StatusClose = 65; // 已关闭 （支付超时，需要关闭）
    // 自设状态
    public const int  StatusDiscard = 70; // 已丢弃（管理员手动关闭）
    public const int  StatusUnknown = 100; // 内部错误

    // https://pay.weixin.qq.com/docs/merchant/apis/jsapi-payment/payment-notice.html
    public const array MapText2Status = [
        'SUCCESS' => self::StatusSuccess,
        'NOTPAY' => self::StatusNotPay, // 未支付
        'CLOSED' => self::StatusClose, // 已关闭
        'REVOKED' => self::StatusRevoked, // 已撤销（付款码支付）
        'USERPAYING' => self::StatusUserPaying, // 用户支付中（付款码支付）
        'PAYERROR' => self::StatusError, //支付失败(其他原因，如银行返回失败)
    ];
    public const array MapStatus = [
        self::StatusCreate => '待支付',
        self::StatusSuccess => '支付成功',
        self::StatusNotPay => '未支付',
        self::StatusClose => '已关闭',
        self::StatusRevoked => '已撤销',
        self::StatusUserPaying => '支付中',
        self::StatusError => '支付失败',
        self::StatusDiscard => '已丢弃',
        self::StatusUnknown => '内部错误'
    ];

    public const int RefundStatusSuccess = 1; // 退款成功
    public const int RefundStatusClosed = 2; // 退款关闭
    public const int RefundStatusProcessing = 3; // 退款处理中
    public const int RefundStatusAbnormal = 4; // 退款异常

    public const array MapText2RefundStatus = [
        'SUCCESS' => self::RefundStatusSuccess,
        'CLOSED' => self::RefundStatusClosed,
        'PROCESSING' => self::RefundStatusProcessing,
        'ABNORMAL' => self::RefundStatusAbnormal
    ];

    public const array MapRefundStatus = [
        self::RefundStatusSuccess => '退款成功',
        self::RefundStatusClosed => '退款关闭',
        self::RefundStatusProcessing => '退款处理中',
        self::RefundStatusAbnormal => '退款异常'
    ];

    public const int  ChannelWepay = 1; // 微信支付
    public const int  ChannelAlipay = 2; // 支付宝

    public const array MapChannel = [
        self::ChannelWepay => '微信',
        self::ChannelAlipay => '支付宝'
    ];

    public const int  TradeTypeJsapi = 1;
    public const int TradeTypeMini = 2;

    public const array MapTradeType = [
        self::TradeTypeJsapi => 'JSAPI',
        self::TradeTypeMini => 'Mini', // 小程序
    ];

    public int $app = 0; // 用于区分来源应用
    public int $user_id = 0;
    public int $channel = 0; // 渠道
    public int $trade_type = 0; // 场景

    public string $appid = ''; // 微信 ID（非支付）
    public string $mchid = ''; // 直连商户号
    public string $openid = ''; // 用户 openid
    public int $amount = 0; // 订单金额
    public int $currency = 1; // 默认 CNY

    public string $metadata = ''; // 商品信息
    public string $response = ''; // 响应信息
    public int $status = self::StatusCreate;
    public string $rndcode = '1'; // 随机码
    public string $message = ''; // 提示信息


    public string $transaction_id = '';
    public int $success_time = 0;

    // 退款相关字段
    public int $refund_at = 0; // 申请退款时间
    public int $refund_amt = 0; // 申请退款金额
    public int $refund_status = 0; // 退款状态
    public string $refund_id = ''; // 退款单号
    public int $refund_time = 0; // 实际退款时间
    public int $refund_amount = 0; // 实际退款金额

    public function beforeCreate()
    {
        $this->rndcode = MyHelperFacade::random(0, 5);
    }

    public const array CURRENCY = ['CNY', 'CNY'];

    public static function getCurrencyText(int $current)
    {
        return self::CURRENCY[$current] ?? 'CNY';
    }

    public function __get(string $property)
    {
        if ($property == 'out_refund_no') {
            return $this->getOutRefundNo();
        } elseif ($property == 'out_trade_no') {
            return $this->getOutTradeNo();
        }
        return parent::__get($property);
    }


    public function getMetadata(): array
    {
        return empty($this->metadata) ? [] : json_decode($this->metadata, true);
    }

    public function getResponse(): array
    {
        return !!$this->response ? json_decode($this->response, true) : [];
    }

    /**
     * 处于支付成功状态中
     * @return bool
     */
    public function isPaySuccess(bool $notRefund = true): bool
    {
        $rst = $this->status == self::StatusSuccess && $this->success_time > 0;
        if ($notRefund) {
            return $rst && $this->refund_at < 1;
        }
        return $rst;
    }

    /**
     * 处于退款成功状态中
     * @return bool
     */
    public function isRefundSuccess(): bool
    {
        return $this->refund_time > 0;
    }

    /**
     * 是否处于退款中状态
     * @return bool
     */
    public function isRefunding(): bool
    {
        // 申请了退款，但退款未完成
        return $this->refund_at > 0 && $this->refund_time == 0;
    }

    /**
     * 能否继续支付
     * @return bool
     */
    public function continuePayStatus(): bool
    {
        return in_array($this->status, [self::StatusCreate, self::StatusRevoked, self::StatusNotPay]);
    }


    public function getOutTradeNo(): string
    {
        return join('_', [$this->id, $this->created_at, $this->rndcode]);
    }

    public function getOutRefundNo(): string
    {
        return join('_', [$this->id, $this->rndcode, $this->created_at]);
    }
}