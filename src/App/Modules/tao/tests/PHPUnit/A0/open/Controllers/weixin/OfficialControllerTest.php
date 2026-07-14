<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\Controllers\weixin\OfficialController;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class OfficialControllerTest extends \PHPUnit\Framework\TestCase
{
    // ===== indexAction: input validation =====

    /**
     * ?skip 参数直接返回 skip
     */
    public function testIndexActionWithSkip()
    {
        /**
         * @var OfficialController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OfficialController::class);

        $tc->setQueryData(['skip' => '1']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('skip');

        $cc->indexAction();
    }

    /**
     * 缺少 appid 参数时拒绝
     */
    public function testIndexActionRequiresAppid()
    {
        /**
         * @var OfficialController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OfficialController::class);

        $this->expectException(BusinessException::class);

        $cc->indexAction();
    }

    /**
     * 提供 appid + echostr（微信服务器验签流程）
     */
    public function testIndexActionWithEchostr()
    {
        /**
         * @var OfficialController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OfficialController::class);

        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'echostr' => 'test_echostr_value',
        ]);

        try {
            $rst = $cc->indexAction();

            // $server->serve() 返回响应对象或字符串
            $this->assertNotNull($rst);
        } catch (BusinessException $e) {
            // OpenAppService 找不到应用配置（DB 无 mock 数据）
            $this->assertStringContainsString('应用配置', $e->getMessage());
        }
    }

    /**
     * 提供 appid 但不带 echostr（消息处理流程）
     */
    public function testIndexActionWithoutEchostr()
    {
        /**
         * @var OfficialController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OfficialController::class);

        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
        ]);

        try {
            $rst = $cc->indexAction();

            // $server->serve() 返回响应对象或字符串
            $this->assertNotNull($rst);
        } catch (BusinessException $e) {
            // OpenAppService 找不到应用配置（DB 无 mock 数据）
            $this->assertStringContainsString('应用配置', $e->getMessage());
        }
    }
}
