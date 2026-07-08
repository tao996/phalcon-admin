<?php

namespace Tests\Unit\tao996\phax\Support;

use Phax\Support\Config;
use Phalcon\Config\Config as PhalconConfig;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private static Config $config;

    public static function setUpBeforeClass(): void
    {
        // 使用反射设置静态 $config 属性，避免加载真实文件
        $ref = new \ReflectionClass(Config::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, new PhalconConfig([
            'app' => [
                'title' => '测试项目',
                'demo' => true,
                'test' => [
                    'open' => true,
                    'tokens' => ['token1', 'token2'],
                ],
                'superAdmin' => [1, 2, 1000],
                'default' => 'defaultProject',
                'sites' => [
                    'projectA' => [
                        'domains' => ['example.com', 'test.example.com'],
                        'namespace' => 'App\Projects\ProjectA\Controllers',
                    ],
                    'projectB' => ['simple-project.com'],
                ],
                'timezone' => 'UTC',
                'cdn_locate' => 'cn',
            ],
            'db' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
            'nested' => [
                'level1' => [
                    'level2' => 'deepValue',
                    'flag' => false,
                    'count' => 0,
                    'positive' => 5,
                ],
            ],
        ]));
        $prop->setAccessible(false);

        self::$config = new Config(\Phax\Foundation\Application::di());
    }

    // ============================================================
    //  path()
    // ============================================================

    public function testPathReturnsCorrectValue(): void
    {
        $this->assertEquals('测试项目', self::$config->path('app.title'));
    }

    public function testPathReturnsDefaultForMissing(): void
    {
        $this->assertNull(self::$config->path('not.exists'));
        $this->assertEquals('defaultVal', self::$config->path('not.exists', 'defaultVal'));
    }

    public function testPathReturnsConfigObject(): void
    {
        $result = self::$config->path('app');
        $this->assertInstanceOf(PhalconConfig::class, $result);
    }

    // ============================================================
    //  getArray()
    // ============================================================

    public function testGetArrayReturnsArray(): void
    {
        $result = self::$config->getArray('app.superAdmin');
        $this->assertIsArray($result);
        $this->assertEquals([1, 2, 1000], $result);
    }

    public function testGetArrayReturnsEmptyForMissing(): void
    {
        $this->assertEquals([], self::$config->getArray('not.exists'));
    }

    public function testGetArrayConvertsConfigToArray(): void
    {
        $result = self::$config->getArray('app.sites');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('projectA', $result);
    }

    // ============================================================
    //  getString()
    // ============================================================

    public function testGetStringReturnsString(): void
    {
        $this->assertEquals('UTC', self::$config->getString('app.timezone'));
    }

    public function testGetStringReturnsEmptyForMissing(): void
    {
        $this->assertEquals('', self::$config->getString('not.exists'));
    }

    public function testGetStringReturnsEmptyForNonString(): void
    {
        $this->assertEquals('', self::$config->getString('app.demo'));
    }

    // ============================================================
    //  getBoolean()
    // ============================================================

    public function testGetBooleanTrueForExplicitBool(): void
    {
        $this->assertTrue(self::$config->getBoolean('app.demo'));
    }

    public function testGetBooleanFalseForExplicitBool(): void
    {
        $this->assertFalse(self::$config->getBoolean('nested.level1.flag'));
    }

    public function testGetBooleanFalseByDefault(): void
    {
        $this->assertFalse(self::$config->getBoolean('not.exists'));
    }

    public function testGetBooleanForNumericPositive(): void
    {
        $this->assertTrue(self::$config->getBoolean('nested.level1.positive'));
    }

    public function testGetBooleanForNumericZero(): void
    {
        $this->assertFalse(self::$config->getBoolean('nested.level1.count'));
    }

    public function testGetBooleanForNonEmptyString(): void
    {
        $this->assertTrue(self::$config->getBoolean('app.cdn_locate'));
    }

    // ============================================================
    //  isDemo()
    // ============================================================

    public function testIsDemo(): void
    {
        $this->assertTrue(self::$config->isDemo());
    }

    // ============================================================
    //  isTest()
    // ============================================================

    public function testIsTest(): void
    {
        $this->assertTrue(self::$config->isTest());
    }

    // ============================================================
    //  getTestUsers()
    // ============================================================

    public function testGetTestUsers(): void
    {
        $this->assertEquals(['token1', 'token2'], self::$config->getTestUsers());
    }

    // ============================================================
    //  getSuperAdminIds()
    // ============================================================

    public function testGetSuperAdminIds(): void
    {
        $this->assertEquals([1, 2, 1000], self::$config->getSuperAdminIds());
    }

    // ============================================================
    //  getProject() / getProjectWithConfig()
    // ============================================================

    public function testGetProjectReturnsDefault(): void
    {
        $this->assertEquals('defaultProject', self::$config->getProject());
    }

    public function testGetProjectWithConfigReturnsDefaultsNoHost(): void
    {
        $result = self::$config->getProjectWithConfig();
        $this->assertIsArray($result);
        $this->assertEquals('defaultProject', $result['name']);
        $this->assertStringContainsString('defaultProject', $result['namespace']);
        $this->assertStringContainsString('defaultProject', $result['viewpath']);
    }
}
