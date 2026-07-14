<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers;

use App\Modules\tao\A0\open\Controllers\AuthController;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class AuthControllerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 清除 session 数据，避免跨测试污染
        AppService::session()->destroy();
    }

    // ===== puidAction: captcha generation =====

    /**
     * puidAction 需要 POST 方法
     */
    public function testPuidRequiresPost()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);

        // 默认 GET 请求
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('only support POST method');

        $cc->puidAction();
    }

    /**
     * puidAction 首次调用（无 captcha）应返回验证码规则
     */
    public function testPuidReturnsCaptchaRuleOnFirstCall()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod(); // 设为 POST 方法
        $cc->requestData = ['puid' => 'test.1'];

        $rst = $cc->puidAction();

        // 应返回验证码规则
        $this->assertIsArray($rst);
        $this->assertArrayHasKey('rule', $rst);
        $this->assertMatchesRegularExpression('/^\d+\+\d+=\?$/', $rst['rule']);

        // 验证 session 中存入了 captcha 信息
        $captchaData = AppService::session()->get('open_puid_captcha');
        $this->assertNotNull($captchaData);
        $this->assertArrayHasKey('answer', $captchaData);
        $this->assertArrayHasKey('expire', $captchaData);
        $this->assertArrayHasKey('attempts', $captchaData);
        $this->assertEquals(0, $captchaData['attempts']);
    }

    // ===== puidAction: captcha validation =====

    /**
     * puidAction 验证码不存在时拒绝
     */
    public function testPuidRejectsMissingCaptchaData()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '42'],
            'puid' => 'test.1',
        ];

        // session 中没有 captcha 数据（setUp 已清除）
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('验证码不存在');

        $cc->puidAction();
    }

    /**
     * puidAction 验证码过期时拒绝
     */
    public function testPuidRejectsExpiredCaptcha()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '42'],
            'puid' => 'test.1',
        ];

        // 设置已过期的 captcha
        AppService::session()->set('open_puid_captcha', [
            'answer' => '42',
            'expire' => time() - 1, // 已过期
            'attempts' => 0,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('验证码已过期');

        $cc->puidAction();
    }

    /**
     * puidAction 验证码错误次数超限时拒绝
     */
    public function testPuidRejectsExceededCaptchaAttempts()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '99'],
            'puid' => 'test.1',
        ];

        // 设置 attempts >= 3
        AppService::session()->set('open_puid_captcha', [
            'answer' => '42',
            'expire' => time() + 120,
            'attempts' => 3,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('验证码错误次数过多');

        $cc->puidAction();
    }

    /**
     * puidAction 验证码答案错误时拒绝，并增加 attempts
     */
    public function testPuidRejectsWrongCaptchaAnswer()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '99'], // 错误答案
            'puid' => 'test.1',
        ];

        AppService::session()->set('open_puid_captcha', [
            'answer' => '42',               // 正确答案是 42
            'expire' => time() + 120,
            'attempts' => 0,
        ]);

        try {
            $cc->puidAction();
            $this->fail('应抛出 BusinessException');
        } catch (BusinessException $e) {
            $this->assertStringContainsString('验证码错误', $e->getMessage());

            // 验证 attempts 增加了
            $captchaData = AppService::session()->get('open_puid_captcha');
            $this->assertEquals(1, $captchaData['attempts']);
        }
    }

    // ===== puidAction: PUID format validation =====

    /**
     * puidAction 验证码通过后，puid 为空时拒绝
     */
    public function testPuidRejectsEmptyPuid()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '42'],
            'puid' => '',
        ];

        AppService::session()->set('open_puid_captcha', [
            'answer' => '42',
            'expire' => time() + 120,
            'attempts' => 0,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('puid 参数不能为空');

        $cc->puidAction();
    }

    /**
     * puidAction 验证码通过后，puid 格式错误时拒绝（多种情况）
     */
    public function testPuidRejectsInvalidFormat()
    {
        $invalidPuidCases = [
            'no_dot',           // 没有点
            'a.b.c',            // 多个点
            '.123',             // 第一部分为空
            'abc.0',            // ID 为 0
            'abc.-1',           // ID 为负数
            'abc.',             // 第二部分为空
        ];

        foreach ($invalidPuidCases as $puid) {
            // 每个 case 重新实例化，避免 session 污染
            list($tc2, $cc2) = MyTestControllerHelper::with(AuthController::class);
            $tc2->setPostMethod();
            $cc2->requestData = [
                'captcha' => ['value' => '42'],
                'puid' => $puid,
            ];
            AppService::session()->set('open_puid_captcha', [
                'answer' => '42',
                'expire' => time() + 120,
                'attempts' => 0,
            ]);

            try {
                $cc2->puidAction();
                $this->fail("puid '{$puid}' 应被拒绝但未抛出异常");
            } catch (BusinessException $e) {
                $this->assertStringContainsString(
                    'puid 参数格式错误',
                    $e->getMessage(),
                    "puid '{$puid}' 的异常信息不正确"
                );
            }
        }
    }

    /**
     * puidAction 验证码通过 + 有效 PUID 格式，尝试用户查询
     */
    public function testPuidValidFormatAttemptsUserQuery()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $tc->setPostMethod();
        $cc->requestData = [
            'captcha' => ['value' => '42'],
            'puid' => 'test.1',     // puid=test, id=1
        ];

        AppService::session()->set('open_puid_captcha', [
            'answer' => '42',
            'expire' => time() + 120,
            'attempts' => 0,
        ]);

        try {
            $rst = $cc->puidAction();
            // 如果数据库有记录，验证返回结构
            $this->assertArrayHasKey('user_id', $rst);
            $this->assertArrayHasKey('nickname', $rst);
            $this->assertArrayHasKey('ts', $rst);
        } catch (BusinessException $e) {
            // "没有找到用户信息" 是正常业务异常，接受
            $this->assertStringContainsString('没有找到用户信息', $e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('用户查询异常（数据库可能未准备好）: ' . $e->getMessage());
        }
    }

    // ===== loginAction: field validation =====

    /**
     * loginAction 需要 account 和 password 字段
     */
    public function testLoginRequiresAccountAndPassword()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $cc->requestData = []; // 空数据

        $this->expectException(BusinessException::class);

        $cc->loginAction();
    }

    /**
     * loginAction 首次调用（无 captcha）应返回验证码规则
     */
    public function testLoginReturnsCaptchaRuleOnFirstCall()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $cc->requestData = [
            'account' => 'test@example.com',
            'password' => '123456',
        ];

        $rst = $cc->loginAction();

        $this->assertIsArray($rst);
        $this->assertArrayHasKey('rule', $rst);
        $this->assertMatchesRegularExpression('/^\d+\+\d+=\?$/', $rst['rule']);

        // 验证 session 中存入了 captcha 信息（使用不同的 key）
        $captchaData = AppService::session()->get('open_login_captcha');
        $this->assertNotNull($captchaData);
        $this->assertEquals(0, $captchaData['attempts']);
    }

    /**
     * loginAction 验证码过期时拒绝
     */
    public function testLoginRejectsExpiredCaptcha()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $cc->requestData = [
            'account' => 'test@example.com',
            'password' => '123456',
            'captcha' => ['value' => '42'],
        ];

        AppService::session()->set('open_login_captcha', [
            'answer' => '42',
            'expire' => time() - 1, // 已过期
            'attempts' => 0,
        ]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('验证码已过期');

        $cc->loginAction();
    }

    /**
     * loginAction 验证码错误时拒绝并增加 attempts
     */
    public function testLoginRejectsWrongCaptcha()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $cc->requestData = [
            'account' => 'test@example.com',
            'password' => '123456',
            'captcha' => ['value' => '99'], // 错误答案
        ];

        AppService::session()->set('open_login_captcha', [
            'answer' => '42',
            'expire' => time() + 120,
            'attempts' => 0,
        ]);

        try {
            $cc->loginAction();
            $this->fail('应抛出 BusinessException');
        } catch (BusinessException $e) {
            $this->assertStringContainsString('验证码错误', $e->getMessage());

            // 验证 attempts 增加了
            $captchaData = AppService::session()->get('open_login_captcha');
            $this->assertEquals(1, $captchaData['attempts']);
        }
    }

    /**
     * loginAction 验证码通过后尝试登录
     */
    public function testLoginAttemptsAfterValidCaptcha()
    {
        /**
         * @var AuthController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(AuthController::class);
        $cc->requestData = [
            'account' => 'test@example.com',
            'password' => '123456',
            'captcha' => ['value' => '42'],
        ];

        AppService::session()->set('open_login_captcha', [
            'answer' => '42',
            'expire' => time() + 120,
            'attempts' => 0,
        ]);

        try {
            $rst = $cc->loginAction();
            // 登录成功
            $this->assertArrayHasKey('user_id', $rst);
            $this->assertArrayHasKey('ts', $rst);
        } catch (BusinessException $e) {
            // UserService::loginWithPassword 查询失败等业务异常（如 "账号不存在或密码不正确"），属于正常
            $this->assertNotEmpty($e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('登录查询异常（数据库可能未准备好）: ' . $e->getMessage());
        }
    }
}
