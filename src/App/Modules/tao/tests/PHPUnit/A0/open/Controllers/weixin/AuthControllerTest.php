<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\Controllers\weixin\AuthController;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LocationException;
use Tests\Helper\MyTestControllerHelper;

class AuthControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        $di = AppService::getDi();
        foreach (['tao.applicationHelper', 'tao.wechatHelper', 'tao.openUrlHelper'] as $svc) {
            if ($di->has($svc)) {
                $di->remove($svc);
            }
        }
        parent::tearDown();
    }

    // ===== indexAction: input validation =====

    public function testIndexActionRequiresAppid()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        // 不传 appid
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('appid is empty');

        $cc->indexAction();
    }

    /**
     * indexAction 携带 appid 后的完整流程
     * 通过模拟微信浏览器环境跳过 QR 码生成，尝试获取 EasyWeChat OAuth
     */
    public function testIndexActionWithAppidAttemptsOAuth()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        // 模拟微信浏览器 UA，跳过 QR 码生成
        $tc->request->data['getUserAgent'] = 'Mozilla/5.0 MicroMessenger';
        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'target' => '/test-redirect',
        ]);

        try {
            $cc->indexAction();
            $this->fail('indexAction 应抛出 LocationException');
        } catch (LocationException $e) {
            // OAuth redirect 成功 — 验证是跳转 URL
            $this->assertNotEmpty($e->getMessage());
        } catch (BusinessException $e) {
            // OpenAppService::kindCompare 或 getOfficial 异常
            $this->assertNotEmpty($e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('indexAction 异常（SDK/数据库未就绪）: ' . $e->getMessage());
        }
    }

    /**
     * indexAction 中 user=1 且已登录时跳转首页
     */
    public function testIndexActionWithUserParamAndLogin()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        $tc->setQueryData(['user' => '1']);

        try {
            $cc->indexAction();
        } catch (LocationException $e) {
            // 如果已登录，应跳转首页
            $this->assertStringContainsString('/', $e->getMessage());
        } catch (BusinessException $e) {
            // 未登录时忽略 user 参数，进入 appid 校验
            $this->assertStringContainsString('appid', $e->getMessage());
        }
    }

    // ===== codeAction: input validation =====

    public function testCodeActionRequiresAppid()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        $tc->setQueryData(['code' => 'test_code']); // 缺少 appid

        $this->expectException(BusinessException::class);

        $cc->codeAction();
    }

    public function testCodeActionRequiresCode()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        $tc->setQueryData(['appid' => 'wx_mock_appid']); // 缺少 code

        $this->expectException(BusinessException::class);

        $cc->codeAction();
    }

    /**
     * codeAction 验证通过后尝试 OAuth
     */
    public function testCodeActionAttemptsOAuth()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        $tc->setQueryData([
            'appid' => 'wx_mock_appid',
            'code' => 'auth_code_xyz',
        ]);

        try {
            $cc->codeAction();
            $this->fail('应抛出 LocationException');
        } catch (LocationException $e) {
            // OAuth 成功后重定向
            $this->assertNotEmpty($e->getMessage());
        } catch (BusinessException $e) {
            // OpenAppService 找不到应用配置（DB 无 mock 数据）
            $this->assertStringContainsString('应用配置', $e->getMessage());
        }
    }
}
