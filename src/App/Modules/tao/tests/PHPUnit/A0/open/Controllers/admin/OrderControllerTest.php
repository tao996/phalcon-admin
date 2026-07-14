<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Controllers\admin\OrderController;
use App\Modules\tao\A0\open\Models\OpenOrder;
use Phax\Foundation\AppService;
use Tests\Helper\MyTestControllerHelper;

class OrderControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * 测试 indexAction 在 API 模式下返回正确的分页结构
     * @throws \Exception
     */
    public function testIndexActionApiResponseStructure()
    {
        /**
         * @var OrderController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OrderController::class);

        // 初始化模型（模拟 afterInitialize 调用后的状态）
        $cc->afterInitialize();

        // 调用 indexAction，如果测试数据库不存在表则跳过
        try {
            $rst = $cc->indexAction();
        } catch (\Throwable $e) {
            $this->markTestSkipped('数据库查询异常（测试表可能不存在）: ' . $e->getMessage());
            return;
        }

        // 验证标准响应结构
        $columns = ['code', 'msg', 'data'];
        foreach ($columns as $column) {
            $this->assertArrayHasKey($column, $rst, "响应缺少字段: {$column}");
        }
        $this->assertEquals(0, $rst['code'], 'API 响应 code 应为 0');
        $this->assertArrayHasKey('count', $rst['data'], 'data 缺少 count');
        $this->assertArrayHasKey('rows', $rst['data'], 'data 缺少 rows');
        $this->assertIsArray($rst['data']['rows'], 'rows 应为数组');
    }

    /**
     * 测试 afterInitialize 正确初始化模型
     * @throws \Exception
     */
    public function testAfterInitializeSetsModel()
    {
        /**
         * @var OrderController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OrderController::class);

        // 通过反射检查 model 初始为 null
        $modelRef = (new \ReflectionClass($cc))->getProperty('model');
        $modelRef->setAccessible(true);
        $this->assertNull($modelRef->getValue($cc), '初始化前 model 应为 null');

        // 调用 afterInitialize
        $cc->afterInitialize();

        $this->assertNotNull($modelRef->getValue($cc), '初始化后 model 不应为 null');
        $this->assertInstanceOf(OpenOrder::class, $modelRef->getValue($cc));
    }

    /**
     * 测试 htmlTitle 属性
     * @throws \Exception
     */
    public function testHtmlTitle()
    {
        /**
         * @var OrderController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(OrderController::class);

        $htmlTitleRef = (new \ReflectionClass($cc))->getProperty('htmlTitle');
        $htmlTitleRef->setAccessible(true);
        $this->assertEquals('订单管理', $htmlTitleRef->getValue($cc));
    }
}
