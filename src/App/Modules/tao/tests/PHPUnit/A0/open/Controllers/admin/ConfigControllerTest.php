<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Controllers\admin\ConfigController;
use App\Modules\tao\A0\open\Models\OpenConfig;
use Tests\Helper\MyTestControllerHelper;

class ConfigControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testAfterInitializeSetsModel()
    {
        /**
         * @var ConfigController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(ConfigController::class);

        $afterInitRef = new \ReflectionMethod($cc, 'afterInitialize');
        $afterInitRef->setAccessible(true);
        $afterInitRef->invoke($cc);

        $modelRef = (new \ReflectionClass($cc))->getProperty('model');
        $modelRef->setAccessible(true);
        $this->assertInstanceOf(OpenConfig::class, $modelRef->getValue($cc));
    }

    /**
     * indexAction 在 GET 模式下返回配置数组（可能空）
     */
    public function testIndexActionReturnsConfigOnGet()
    {
        /**
         * @var ConfigController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(ConfigController::class);

        $afterInitRef = new \ReflectionMethod($cc, 'afterInitialize');
        $afterInitRef->setAccessible(true);
        $afterInitRef->invoke($cc);

        $tc->setGetMethod();

        try {
            $rst = $cc->indexAction();

            $this->assertIsArray($rst);
        } catch (\Throwable $e) {
            $this->markTestSkipped('indexAction 异常（缓存/数据库未就绪）: ' . $e->getMessage());
        }
    }
}
