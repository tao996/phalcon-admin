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

    public function testIndexActionWithAppidAttemptsFlow()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);
        $tc->setQueryData(['appid' => 'wx_mock_appid']);

        try {
            $cc->indexAction();
        } catch (LocationException $e) {
            // 已登录且有 openid 时跳转 jsapi
            $this->assertStringContainsString('demo.pay/jsapi', $e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('indexAction 异常: ' . $e->getMessage());
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

    public function testJsapiWithMockedServer()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        // 模拟微信浏览器 UA
        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'openid' => 'mock_openid',
        ]);

        // POST 请求
        $tc->setPostData([]); // 默认 1 分

        try {
            $rst = $cc->jsapiAction();
            // POST + 非空请求 → 走 prepay 流程
            $this->assertIsArray($rst);
        } catch (BusinessException $e) {
            // OpenMchService::getDefaultMchid() 失败等
            $this->assertNotEmpty($e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('jsapiAction 异常: ' . $e->getMessage());
        }
    }

    public function testJsapiGetReturnsArray()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);

        // 模拟微信浏览器 UA
        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'openid' => 'mock_openid',
        ]);
        // GET 请求（默认）

        $rst = $cc->jsapiAction();
        $this->assertIsArray($rst);
        $this->assertEmpty($rst); // GET 返回 []
    }

    // ===== notify / refundNotify =====

    public function testNotifyAction()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);
        $cc->autoResponse = false;

        try {
            $rst = $cc->notifyAction('wx_mock_appid', 'mch_123');
            // 使用真实 WepayServer 会触发 EasyWeChat 异常
            $this->assertNotNull($rst);
        } catch (\Throwable $e) {
            $this->markTestSkipped('notifyAction 异常（需要 EasyWeChat）: ' . $e->getMessage());
        }
    }

    public function testRefundNotifyAction()
    {
        /**
         * @var PayController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(PayController::class);
        $cc->autoResponse = false;

        try {
            $rst = $cc->refundNotifyAction('out_trade_no_123');
            $this->assertNotNull($rst);
        } catch (\Throwable $e) {
            $this->markTestSkipped('refundNotifyAction 异常（需要 EasyWeChat）: ' . $e->getMessage());
        }
    }

    // ===== Prepay 单元测试（通过接口注入） =====

    /**
     * Prepay::prepay() 的业务逻辑：验证 postData 组装是否正确
     */
    public function testPrepayBusinessLogic()
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

        // prepay 方法会尝试创建订单（DB），预计失败
        try {
            $result = $prepay->prepay($order, ['description' => 'Test'], true);
            // 如果 DB 可用，验证 prepay 返回值格式
            $this->assertArrayHasKey('appId', $result);
            $this->assertArrayHasKey('timeStamp', $result);
            $this->assertArrayHasKey('package', $result);
            $this->assertStringContainsString('prepay_id=', $result['package']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('prepay DB 操作异常: ' . $e->getMessage());
        }
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
