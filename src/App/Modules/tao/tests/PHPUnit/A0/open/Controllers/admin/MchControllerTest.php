<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Controllers\admin\MchController;
use App\Modules\tao\A0\open\Models\OpenMch;
use Phax\Support\Exception\BusinessException;
use Tests\Helper\MyTestControllerHelper;

class MchControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testAfterInitializeSetsModel()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $cc->afterInitialize();

        $modelRef = (new \ReflectionClass($cc))->getProperty('model');
        $modelRef->setAccessible(true);
        $this->assertInstanceOf(OpenMch::class, $modelRef->getValue($cc));
    }

    public function testHtmlTitle()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);

        $ref = (new \ReflectionClass($cc))->getProperty('htmlTitle');
        $ref->setAccessible(true);
        $this->assertEquals('商户', $ref->getValue($cc));
    }

    public function testModelHiddenColumns()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);

        $ref = (new \ReflectionClass($cc))->getProperty('modelHiddenColumns');
        $ref->setAccessible(true);
        $this->assertEquals(['secret_key'], $ref->getValue($cc));
    }

    public function testSuperAdminActions()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);

        $ref = (new \ReflectionClass($cc))->getProperty('superAdminActions');
        $ref->setAccessible(true);
        $this->assertEquals('*', $ref->getValue($cc));
    }

    // ===== beforeModelAssign =====

    public function testBeforeModelAssignRequiresMchid()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $cc->afterInitialize();

        $method = new \ReflectionMethod($cc, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('商户号ID');

        $method->invoke($cc, [
            'secret_key' => 'key123',
            'pubkey_id' => 'pub456',
        ]);
    }

    public function testBeforeModelAssignRequiresSecretKey()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $cc->afterInitialize();

        $method = new \ReflectionMethod($cc, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('秘钥');

        $method->invoke($cc, [
            'mchid' => 'mch123',
            'pubkey_id' => 'pub456',
        ]);
    }

    public function testBeforeModelAssignRequiresPubkeyId()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $cc->afterInitialize();

        $method = new \ReflectionMethod($cc, 'beforeModelAssign');
        $method->setAccessible(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('微信支付公钥');

        $method->invoke($cc, [
            'mchid' => 'mch123',
            'secret_key' => 'key123',
        ]);
    }

    // ===== certAction validation =====

    public function testCertActionRequiresPost()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('only support POST method');

        $cc->certAction();
    }

    public function testCertActionRequiresIdAndName()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $tc->setPostData([]); // 空数据

        $this->expectException(BusinessException::class);

        $cc->certAction();
    }

    public function testCertActionRejectsInvalidCertName()
    {
        /**
         * @var MchController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
        $tc->setPostData([
            'id' => '1',
            'name' => 'invalid_cert_name',
        ]);

        $rst = $cc->certAction();

        // MchController 对非法 name 返回 error 数组（不抛异常）
        $this->assertIsArray($rst);
        $this->assertArrayHasKey('code', $rst);
        $this->assertEquals(500, $rst['code']); // error 默认 code 500
        $this->assertStringContainsString('不支持上传的证书类型', $rst['msg']);
    }

    /**
     * certAction 可接受的证书名称
     */
    public function testCertActionAcceptsValidCertNames()
    {
        $validNames = ['private_key', 'certificate', 'pubkey', 'platform_cert'];

        foreach ($validNames as $name) {
            list($tc, $cc) = MyTestControllerHelper::with(MchController::class);
            $tc->setPostData([
                'id' => '99999',
                'name' => $name,
            ]);

            try {
                $cc->certAction();
            } catch (\Exception $e) {
                // mustFindFirst(99999) → 说明 name 校验已通过
                $this->assertStringContainsString('找不到符合要求的记录', $e->getMessage(),
                    "name='{$name}' 应通过校验: " . $e->getMessage());
            }
        }
    }
}
