<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Controllers\admin\AppController;
use App\Modules\tao\A0\open\Models\OpenApp;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class AppControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AppController
     */
    protected $controller;
    protected MyTestControllerHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        list($this->helper, $this->controller) = MyTestControllerHelper::with(AppController::class);
        $this->controller->afterInitialize();
    }

    public function testAfterInitializeSetsModel()
    {
        $modelRef = (new \ReflectionClass($this->controller))->getProperty('model');
        $modelRef->setAccessible(true);
        $this->assertInstanceOf(OpenApp::class, $modelRef->getValue($this->controller));
    }

    public function testHtmlTitle()
    {
        $ref = (new \ReflectionClass($this->controller))->getProperty('htmlTitle');
        $ref->setAccessible(true);
        $this->assertEquals('应用', $ref->getValue($this->controller));
    }

    public function testModelHiddenColumns()
    {
        $ref = (new \ReflectionClass($this->controller))->getProperty('modelHiddenColumns');
        $ref->setAccessible(true);
        $this->assertEquals(['secret'], $ref->getValue($this->controller));
    }

    public function testModelOrderBy()
    {
        $ref = (new \ReflectionClass($this->controller))->getProperty('modelOrderBy');
        $ref->setAccessible(true);
        $this->assertEquals('sort desc,id desc', $ref->getValue($this->controller));
    }

    public function testAllowModifyFields()
    {
        $ref = (new \ReflectionClass($this->controller))->getProperty('allowModifyFields');
        $ref->setAccessible(true);
        $this->assertEquals(['status', 'sort', 'online', 'sandbox'], $ref->getValue($this->controller));
    }

    // ===== beforeModelAssign =====

    public function testBeforeModelAssignRequiresAppid()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('appid');

        $method->invoke($this->controller, [
            'platform' => 'wechat',
            'title' => 'Test',
            'kind' => 'gzh',
            'secret' => 'sec',
        ]);
    }

    public function testBeforeModelAssignRequiresPlatform()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('平台');

        $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'title' => 'Test',
            'kind' => 'gzh',
            'secret' => 'sec',
        ]);
    }

    public function testBeforeModelAssignRequiresTitle()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('应用名称');

        $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'platform' => 'wechat',
            'kind' => 'gzh',
            'secret' => 'sec',
        ]);
    }

    public function testBeforeModelAssignRequiresKind()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('应用类型');

        $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'platform' => 'wechat',
            'title' => 'Test',
            'secret' => 'sec',
        ]);
    }

    public function testBeforeModelAssignRequiresSecret()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('secret');

        $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'platform' => 'wechat',
            'title' => 'Test',
            'kind' => 'gzh',
        ]);
    }

    public function testBeforeModelAssignCastsSandbox()
    {
        $method = new \ReflectionMethod($this->controller, 'beforeModelAssign');
        $method->setAccessible(true);

        // sandbox 存在时转为 int
        $result = $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'platform' => 'wechat',
            'title' => 'Test',
            'kind' => 'gzh',
            'secret' => 'sec',
            'sandbox' => true,
        ]);
        $this->assertSame(1, $result['sandbox']);

        // sandbox 无/假值
        $result2 = $method->invoke($this->controller, [
            'appid' => 'wx_test',
            'platform' => 'wechat',
            'title' => 'Test',
            'kind' => 'gzh',
            'secret' => 'sec',
            'sandbox' => false,
        ]);
        $this->assertSame(0, $result2['sandbox']);
    }

    // ===== certAction validation =====

    public function testCertActionRequiresPost()
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('only support POST method');

        $this->controller->certAction();
    }

    public function testCertActionRejectsInvalidCertName()
    {
        $this->helper->setPostData([
            'id' => '1',
            'name' => 'invalid_cert_name',
            'value' => 'content',
        ]);

        $this->expectException(BusinessException::class);

        $this->controller->certAction();
    }

    /**
     * certAction 可接受的证书名称（验证能通过 name 校验阶段）
     */
    public function testCertActionAcceptsValidCertNames()
    {
        $validNames = ['public_key', 'rsa_public_key', 'rsa_private_key'];

        foreach ($validNames as $name) {
            list($h, $cc) = MyTestControllerHelper::with(AppController::class);
            $cc->afterInitialize();
            $h->setPostData([
                'id' => '99999',
                'name' => $name,
                'value' => '',
            ]);

            try {
                $cc->certAction();
            } catch (\Exception $e) {
                // mustFindFirst(99999) → "找不到符合要求的记录" 说明 name 校验已通过
                $this->assertStringContainsString('找不到符合要求的记录', $e->getMessage(),
                    "name='{$name}' 应通过校验: " . $e->getMessage());
            }
        }
    }
}
