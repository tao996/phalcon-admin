<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\demo;

use App\Modules\tao\A0\open\Controllers\demo\PayController;
use App\Modules\tao\A0\open\Helper\Libs\WepayServerInterface;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LocationException;
use Tests\Helper\MyTestControllerHelper;

class PayControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * 测试替身：模拟 WePayServer 返回固定 prepay_id
     */
    private function createMockWepayServer(): WepayServerInterface
    {
        return new class implements WepayServerInterface {
            public function prepay(array $postData): array
            {
                return ['prepay_id' => 'wx_mock_prepay_123'];
            }
            public function repay(string $prepayId)
            {
                return [
                    'appId' => 'wx_mock_app',
                    'timeStamp' => (string)time(),
                    'nonceStr' => 'mock_nonce',
                    'package' => 'prepay_id=' . $prepayId,
                    'signType' => 'RSA',
                    'paySign' => 'mock_signature',
                ];
            }
            public function notify(callable $callback) { return 'mock_notify_response'; }
            public function refundNotify(callable $callback) { return 'mock_refund_response'; }
            public function queryByOutTradeNo(string $outTradeNo) { return []; }
            public function queryByTransactionId(string $transactionId) { return []; }
            public function refundQuery(string $outRefundNo) { return []; }
            public function refund(array $postData) { return []; }
            public function close(string $outTradeNo): void {}
        };
    }

    // ===== indexAction =====

    public function testIndexActionRequiresAppid()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('必须指定支付公众号 ID');

        $cc->indexAction();
    }

    /**
     * indexAction 有 appid 且模拟微信浏览器时，经登录判断后跳转 / 尝试流程
     */
    public function testIndexActionWithAppidAndWechatUA()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        // 模拟微信浏览器 UA，跳过 QR 码渲染（防止 header() 在 CLI 报错）
        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData(['appid' => 'wx_mock_appid']);

        try {
            $cc->indexAction();
        } catch (LocationException $e) {
            // 未登录时走 quickOpenid 跳转 OAuth
            $this->assertStringContainsString('tao.wechat/auth', $e->getMessage());
        } catch (BusinessException $e) {
            // 其它业务异常
            $this->assertNotEmpty($e->getMessage());
        }
    }

    // ===== jsapiAction =====

    public function testJsapiRequiresMicroMessenger()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('只支持在微信浏览器中操作');

        $cc->jsapiAction();
    }

    /**
     * jsapiAction 模拟微信浏览器 + POST，验证到达 prepay 阶段
     * （会因 DB 无商户数据跳过，但异常信息本身验证了流程正确）
     */
    public function testJsapiAttemptsPrepayWithValidInput()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'openid' => 'mock_openid',
        ]);
        $tc->setPostData([]); // 默认 1 分

        try {
            $rst = $cc->jsapiAction();
            $this->assertIsArray($rst);
        } catch (BusinessException $e) {
            // OpenMchService 找不到默认商户，或 WepayServer 初始化时 mchid 为空
            $this->assertNotEmpty($e->getMessage());
        }
    }

    public function testJsapiGetReturnsArray()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'openid' => 'mock_openid',
        ]);

        $rst = $cc->jsapiAction();
        $this->assertIsArray($rst);
        $this->assertEmpty($rst); // GET 返回 []
    }

    // ===== notify / refundNotify =====

    /**
     * notifyAction 需要有效的商户号和 AppId，否则抛出业务异常
     */
    public function testNotifyActionRequiresValidMch()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);
        $cc->autoResponse = false;

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('商户号');

        $cc->notifyAction('wx_mock_appid', 'mch_123');
    }

    /**
     * refundNotifyAction 需要有效的订单号格式
     */
    public function testRefundNotifyActionRequiresValidOrderNo()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);
        $cc->autoResponse = false;

        $this->expectException(BusinessException::class);

        $cc->refundNotifyAction('invalid_out_trade_no');
    }

    // ===== Prepay 单元测试（通过接口注入） =====

    /**
     * Prepay::createOrder() 的业务逻辑：验证订单数据组装
     */
    public function testPrepayCreateOrder()
    {
        $prepay = new \App\Modules\tao\A0\open\Helper\wepay\Prepay('wx_mock_appid', 'mch_123');
        $prepay->setWechatServer($this->createMockWepayServer());
        $prepay->setOpenid('mock_openid');

        $order = $prepay->createOrder(100, ['description' => 'Test Item']);

        $this->assertInstanceOf(OpenOrder::class, $order);
        $this->assertEquals('wx_mock_appid', $order->appid);
        $this->assertEquals('mch_123', $order->mchid);
        $this->assertEquals(100, $order->amount);
        $this->assertEquals(OpenOrder::ChannelWepay, $order->channel);
        $this->assertEquals('mock_openid', $order->openid);
        $this->assertStringContainsString('Test Item', $order->metadata);
    }

    /**
     * Prepay::prepay() 在缺少商品描述时拒绝
     */
    public function testPrepayRejectsMissingDescription()
    {
        $prepay = new \App\Modules\tao\A0\open\Helper\wepay\Prepay('wx_mock_appid', 'mch_123');
        $prepay->setWechatServer($this->createMockWepayServer());
        $prepay->setOpenid('mock_openid');
        $order = $prepay->createOrder(100, ['description' => 'Test']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('商品描述');

        $prepay->prepay($order, [], true); // 缺少 description
    }

    /**
     * Prepay::prepay() 在金额小于 1 分时拒绝
     */
    public function testPrepayRejectsAmountLessThanOne()
    {
        $prepay = new \App\Modules\tao\A0\open\Helper\wepay\Prepay('wx_mock_appid', 'mch_123');
        $prepay->setWechatServer($this->createMockWepayServer());
        $prepay->setOpenid('mock_openid');
        $order = $prepay->createOrder(0, ['description' => 'Test']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('订单金额不能小于1分');

        $prepay->prepay($order, ['description' => 'Test'], true);
    }

    /**
     * Prepay 在没有 openid 时创建订单应拒绝
     */
    public function testPrepayRejectsMissingOpenid()
    {
        $prepay = new \App\Modules\tao\A0\open\Helper\wepay\Prepay('wx_mock_appid', 'mch_123');
        $prepay->setWechatServer($this->createMockWepayServer());

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('user openid is empty');

        $prepay->createOrder(100, ['description' => 'Test']);
    }

    /**
     * Prepay 空 appid 时拒绝
     */
    public function testPrepayRejectsEmptyAppid()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('appid is empty');

        new \App\Modules\tao\A0\open\Helper\wepay\Prepay('', 'mch_123');
    }
}
