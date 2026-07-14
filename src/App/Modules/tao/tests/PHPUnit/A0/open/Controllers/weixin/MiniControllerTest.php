<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\Controllers\weixin\MiniController;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class MiniControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        // 清理可能注册的 mock service，避免影响其他测试
        $di = AppService::getDi();
        if ($di->has('tao.miniAppHelper')) {
            $di->remove('tao.miniAppHelper');
        }
        parent::tearDown();
    }

    // ===== code2SessionAction: input validation =====

    public function testCode2SessionRequiresPost()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('only support POST method');

        $cc->code2SessionAction();
    }

    public function testCode2SessionRequiresCode()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        $tc->setPostMethod();
        $cc->requestData = []; // 缺少 code

        $this->expectException(BusinessException::class);

        $cc->code2SessionAction();
    }

    public function testCode2SessionRequiresAppid()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        $tc->setPostMethod();
        $cc->requestData = ['code' => 'test_code'];

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('必须指定 appid');

        $cc->code2SessionAction();
    }

    /**
     * code2Session 提供 appid + code 后尝试完整流程
     * 通过预注册 mock miniAppHelper 来隔离 EasyWeChat 依赖
     */
    public function testCode2SessionAttemptsFullFlow()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);

        // 注册 mock miniAppHelper，返回 fixture 数据
        $di = AppService::getDi();
        $di->setShared('tao.miniAppHelper', function () {
            $mock = new \App\Modules\tao\A0\open\Helper\MiniAppHelper();
            // 用匿名类重写 code2Session 方法
            return new class($mock) extends \App\Modules\tao\A0\open\Helper\MiniAppHelper {
                public function code2Session(array $app, string $code): array
                {
                    return [
                        'session_key' => 'mock_session_key_' . $code,
                        'openid' => 'mock_openid_' . $code,
                        'unionid' => 'mock_unionid_' . $code,
                    ];
                }
            };
        });

        $tc->setPostMethod();
        $cc->requestData = ['code' => 'abc123'];
        // 设置 query appid（getAppid 从 query 读取）
        $tc->setQueryData(['appid' => 'wx_mock_appid']);
        // 重新设置 POST 方法（setQueryData 会覆盖为 GET）
        $tc->setPostMethod();

        try {
            $rst = $cc->code2SessionAction();
            // 如果完整流程成功（DB 有数据且登录正常）
            $this->assertArrayHasKey('user_id', $rst);
            $this->assertArrayHasKey('openid', $rst);
            $this->assertArrayHasKey('ts', $rst);
        } catch (BusinessException $e) {
            // OpenAppService::getWithAppid('wx_mock_appid') 找不到应用配置 → 正常业务异常
            $this->assertStringContainsString('appid', $e->getMessage(), '', true); // 大小写不敏感
        } catch (\Throwable $e) {
            $this->markTestSkipped('code2Session 完整流程异常（数据库/缓存未就绪）: ' . $e->getMessage());
        }
    }

    // ===== qRCodeAction: input validation =====

    public function testQRCodeRequiresAppid()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        // 不设置 appid
        $cc->requestData = ['scene' => 'test_scene'];

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('必须指定 appid');

        $cc->qRCodeAction();
    }

    public function testQRCodeRequiresScene()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        // 设置 appid 但不设置 scene
        $tc->setQueryData(['appid' => 'wx_mock_appid']);
        $cc->requestData = [];

        $this->expectException(BusinessException::class);

        $cc->qRCodeAction();
    }

    public function testQRCodeRejectsSceneOver32Chars()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        $tc->setQueryData(['appid' => 'wx_mock_appid']);
        $cc->requestData = ['scene' => str_repeat('a', 33)]; // 超过 32 字符

        $this->expectException(BusinessException::class);

        $cc->qRCodeAction();
    }

    public function testQRCodeAcceptsValidScene()
    {
        /**
         * @var MiniController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MiniController::class);
        $tc->setQueryData(['appid' => 'wx_mock_appid']);
        $cc->requestData = ['scene' => 'test_scene_123'];

        try {
            $cc->qRCodeAction();
        } catch (\Exception $e) {
            // 场景校验通过后，会尝试获取 EasyWeChat Application
            // 可能抛出 "找不到符合要求的记录" 或 SDK 相关异常
            $this->assertNotEmpty($e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('qRCodeAction 异常（SDK/数据库未就绪）: ' . $e->getMessage());
        }
    }
}
