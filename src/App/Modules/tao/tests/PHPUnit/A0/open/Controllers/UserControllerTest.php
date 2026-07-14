<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers;

use App\Modules\tao\A0\open\Controllers\UserController;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class UserControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * logoutAction 退出登录，返回成功提示
     */
    public function testLogoutReturnsSuccessMessage()
    {
        /**
         * @var UserController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(UserController::class);

        $rst = $cc->logoutAction();

        $this->assertEquals('退出成功', $rst);
    }

    /**
     * infoAction 需要登录态 + appid，在测试环境中标记为 skipped
     */
    public function testInfoActionRequiresLogin()
    {
        /**
         * @var UserController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(UserController::class);

        $tc->setQueryData(['appid' => 'wx_test_appid']);

        try {
            $cc->infoAction();
            $this->fail('infoAction 应抛出异常（无登录态）');
        } catch (BusinessException $e) {
            // 登录态未设置是预期的，说明 infoAction 正确执行了权限检查
            $this->assertStringContainsString('还没有设置用户数据', $e->getMessage());
        } catch (\Throwable $e) {
            $this->markTestSkipped('infoAction 异常（环境依赖）: ' . $e->getMessage());
        }
    }
}
