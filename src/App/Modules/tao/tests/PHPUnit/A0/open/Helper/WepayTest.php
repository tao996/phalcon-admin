<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Helper;

use App\Modules\tao\tests\Helper\MyTestTaoControllerHelper;
use App\Modules\tao\A0\open\Helper\Libs\WepayServer;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Helper\wepay\Notify;
use App\Modules\tao\A0\open\Helper\wepay\Prepay;
use App\Modules\tao\A0\open\Models\OpenOrder;
use App\Modules\tao\BaseController;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class WepayTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @throws \Exception
     */
    public function testPrepay()
    {
        /**
         * @var $cc BaseController
         */
        list($tc, $cc) = MyTestTaoControllerHelper::with(BaseController::class);

        $tc->login()->initialize();

        $preData = [
            'appid' => 'wx1234567890',
            'mchid' => '1234567890',
            'openid' => 'op123456',
            'prepay_id' => 'test:' . time(),
            'notify_ur' => 'https://xxx',
            'order' => [
                'amount' => rand(1, 10000),
                'metadata' => [
                    'description' => 'my test 1',
                ]
            ],
        ];

        $myOpenMvcHelper = new MyOpenMvcHelper($cc->vv);
        $wepayServer = \Mockery::mock(WepayServer::class);

        $prepay = new Prepay($myOpenMvcHelper, $preData['appid'], $preData['mchid']);
        $prepay->wepayServer = $wepayServer;
        $prepay->setOpenid($preData['openid']);

        $wepayServer->allows('prepay')->with(\Mockery::on(function ($postData) use ($preData) {
            // 提交订单的数字
            $this->assertEquals($preData['notify_ur'], $postData['notify_url']);
            $this->assertEquals($preData['appid'], $postData['appid']);
            $this->assertEquals($preData['mchid'], $postData['mchid']);
            $this->assertEquals($preData['openid'], $postData['payer']['openid']);
            $this->assertEquals($preData['order']['amount'], $postData['amount']['total']);
            return true;
        }))->andReturn(['prepay_id' => $preData['prepay_id']]);

        $wepayServer->allows('repay')->with(\Mockery::on(function ($prepayId) use ($preData) {
            $this->assertEquals($preData['prepay_id'], $prepayId);
            return true;
        }))->andReturn('abc');

        $order = $prepay->createOrder($preData['order']['amount'], $preData['order']['metadata']);
        $rst = $prepay->prepay($order, [
            'description' => $preData['order']['metadata']['description'],
            'notify_url' => $preData['notify_ur']
        ]);
        $this->assertEquals('abc', $rst);
        $this->assertTrue($order->id > 0);
        $this->assertEquals(OpenOrder::StatusCreate, $order->status);

        return $order;
    }

    /**
     * @throws \Exception
     */
    #[Depends('testPrepay')] public function testPayNotify(OpenOrder $order)
    {
        /**
         * @var $cc BaseController
         */
        list ($tc, $cc) = MyTestTaoControllerHelper::with(BaseController::class);

        $tc->login()->initialize();

        $responseData = [
            'mchid' => $order->mchid,
            'appid' => $order->appid,
            'out_trade_no' => $order->getOutTradeNo(),
            'transaction_id' => 'xxx123',
            'trade_type' => 'JSAPI',
            'trade_state' => 'SUCCESS',
            'trade_state_desc' => '支付成功',
            'bank_type' => 'OTHERS',
            'attach' => '',
            'success_time' => '2024-08-29T23:40:38+08:00',
            'payer' => [
                'openid' => $order->openid
            ],
            'amount' => [
                'total' => $order->amount,
                'payer_total' => $order->amount,
                'currency' => 'CNY',
                'payer_currency' => 'CNY'
            ]
        ];

        $myOpenMvcHelper = new MyOpenMvcHelper($cc->vv);
        $wepayServer = \Mockery::mock(WepayServer::class);

        $notify = new Notify($myOpenMvcHelper, $order->appid, $order->mchid);
        $notify->wepayServer = $wepayServer;

        $wepayServer->allows('notify')->with(\Mockery::on(function () use ($notify, $responseData) {
            $notify->handlePaid($responseData); // 处理订单
            return true;
        }))->andReturn(true); // 响应 Response
        $notify->response();

        // 验证订单数据
        $afterOrder = OpenOrder::findFirst($order->id);
        $this->assertEquals(OpenOrder::StatusSuccess, $afterOrder->status);
        $this->assertEquals($responseData['transaction_id'], $afterOrder->transaction_id);
    }
}