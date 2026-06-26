<?php

namespace Tests\Unit\tao996\phax\Support;

use Phax\Support\I18nService;
use PHPUnit\Framework\TestCase;

class I18nServiceTest extends TestCase
{
    private string $originalLang;

    protected function setUp(): void
    {
        parent::setUp();
        // 保存原始语言配置，防止测试间互相影响
        $this->originalLang = I18nService::$lang;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // 恢复原始语言配置
        I18nService::$lang = $this->originalLang;
    }

    /**
     * 基础功能：不传占位符和命名空间，直接返回默认值
     */
    public function testDefaultReturn(): void
    {
        $this->assertEquals('你好', I18nService::translate('greeting', '你好'));
    }

    /**
     * 占位符 :name 替换
     */
    public function testNamedPlaceholder(): void
    {
        $result = I18nService::translate('welcome', '欢迎 :name', ['name' => '张三']);
        $this->assertEquals('欢迎 张三', $result);
    }

    /**
     * 占位符替换
     */
    public function testAtPlaceholder(): void
    {
        $result = I18nService::translate('welcome', '欢迎 @name', ['name' => '张三']);
        $this->assertEquals('欢迎 张三', $result);
    }

    /**
     * 占位符 {name} 替换
     */
    public function testBracePlaceholder(): void
    {
        $result = I18nService::translate('welcome', '欢迎 {name}', ['name' => '张三']);
        $this->assertEquals('欢迎 张三', $result);
    }

    /**
     * 多个占位符同时替换
     */
    public function testMultiplePlaceholders(): void
    {
        $result = I18nService::translate(
            'order',
            '用户 :user 购买了 :product，共 :count 件',
            ['user' => '张三', 'product' => '手机', 'count' => '2']
        );
        $this->assertEquals('用户 张三 购买了 手机，共 2 件', $result);
    }

    /**
     * 传参 key 带 : 前缀（如 [':name' => 'World']）
     */
    public function testPlaceholderKeyWithColonPrefix(): void
    {
        $result = I18nService::translate('test', 'Hello :name', [':name' => 'World']);
        $this->assertEquals('Hello World', $result);
    }

    /**
     * 传参 key 带 @ 前缀
     */
    public function testPlaceholderKeyWithAtPrefix(): void
    {
        $result = I18nService::translate('test', 'Hello @name', ['@name' => 'World']);
        $this->assertEquals('Hello World', $result);
    }

    /**
     * 传参 key 带 $ 前缀
     */
    public function testPlaceholderKeyWithDollarPrefix(): void
    {
        $result = I18nService::translate('test', 'Hello :name', ['$name' => 'World']);
        $this->assertEquals('Hello World', $result);
    }

    /**
     * 空占位符列表，原文不变
     */
    public function testEmptyParams(): void
    {
        $result = I18nService::translate('test', 'Hello World', []);
        $this->assertEquals('Hello World', $result);
    }

    /**
     * 部分占位符不匹配时，未匹配部分保持不变
     */
    public function testPartialMatching(): void
    {
        $result = I18nService::translate('test', 'Hello :name, your :role is active', ['name' => 'Admin']);
        $this->assertEquals('Hello Admin, your :role is active', $result);
    }

    /**
     * 传入 Modules 命名空间但语言包不存在 → 优雅降级返回 default
     */
    public function testModuleNamespaceWithoutLangFile(): void
    {
        $result = I18nService::translate('greeting', '你好', [], 'App\\Modules\\yihe\\Controllers\\IndexController');
        $this->assertEquals('你好', $result);
    }

    /**
     * 传入 Projects 命名空间但语言包不存在 → 优雅降级返回 default
     */
    public function testProjectNamespaceWithoutLangFile(): void
    {
        $result = I18nService::translate('greeting', 'Hello', [], 'App\\Projects\\demo\\Controllers\\IndexController');
        $this->assertEquals('Hello', $result);
    }

    /**
     * 非 App\Modules 或 App\Projects 的命名空间 → 不尝试加载语言包
     */
    public function testUnrelatedNamespace(): void
    {
        $result = I18nService::translate('greeting', 'Hi', [], 'Some\\Other\\Namespace');
        $this->assertEquals('Hi', $result);
    }

    /**
     * 空字符串命名空间 → 行为等同于不传 namespace
     */
    public function testEmptyNamespace(): void
    {
        $result = I18nService::translate('greeting', 'Hello', [], '');
        $this->assertEquals('Hello', $result);
    }

    /**
     * $lang !== 'zh_CN' 时即使没有 namespace 也会尝试加载语言包
     * （语言包不存在则降级返回 default）
     */
    public function testNonChineseLangWithoutNamespace(): void
    {
        I18nService::$lang = 'en_US';
        $result = I18nService::translate('greeting', 'Hello', []);
        $this->assertEquals('Hello', $result);
    }

    /**
     * 占位符替换时保留原始值类型转换（int → string）
     */
    public function testNumericPlaceholderValue(): void
    {
        $result = I18nService::translate('count', '共 :count 条记录', ['count' => 42]);
        $this->assertEquals('共 42 条记录', $result);
    }

    /**
     * 带 namespace 同时有占位符替换
     */
    public function testNamespaceWithParams(): void
    {
        $result = I18nService::translate(
            'welcome',
            '欢迎 :name',
            ['name' => '张三'],
            'App\\Modules\\yihe\\Controllers\\IndexController'
        );
        $this->assertEquals('欢迎 张三', $result);
    }
}
