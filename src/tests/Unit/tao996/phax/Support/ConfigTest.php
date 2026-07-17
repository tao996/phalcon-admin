<?php

namespace Tests\Unit\tao996\phax\Support;

use Phax\Support\Config;
use Phalcon\Config\Config as PhalconConfig;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private static Config $config;
    /** @var mixed 保存原始静态 $config 用于恢复 */
    private static mixed $originalConfig;

    public static function setUpBeforeClass(): void
    {
        // 保存原始 config 以便恢复
        $ref = new \ReflectionClass(Config::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        self::$originalConfig = $prop->getValue(null);
        $prop->setValue(null, new PhalconConfig([
            'app' => [
                'title' => '测试项目',
                'demo' => [
                    'open' => true,
                    'admin' => [
                        'account' => 'admin',
                        'password' => '123456'
                    ],
                ],
                'test' => [
                    'open' => true,
                    'tokens' => ['token1', 'token2'],
                ],
                'assets' => [
                    'cdn' => true,
                    'hosts' => [],
                    'min' => false,
                ],
                'superAdmin' => [1, 2, 1000],
                'timezone' => 'UTC',
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

    public static function tearDownAfterClass(): void
    {
        // 恢复原始静态 config，避免影响后续测试
        $ref = new \ReflectionClass(Config::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, self::$originalConfig);
        $prop->setAccessible(false);
        self::$originalConfig = null;
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
        $this->assertEquals('', self::$config->getString('app.demo.open'));
    }

    // ============================================================
    //  getBoolean()
    // ============================================================

    public function testGetBooleanTrueForExplicitBool(): void
    {
        $this->assertTrue(self::$config->getBoolean('app.demo.open'));
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
        $this->assertTrue(self::$config->getBoolean('app.assets.cdn'));
    }

    // ============================================================
    //  isDemo()
    // ============================================================

    public function testIsDemo(): void
    {
        $this->assertTrue(self::$config->getBoolean('app.demo.open'));
    }

    // ============================================================
    //  isTest()
    // ============================================================

    public function testIsTest(): void
    {
        $this->assertTrue(self::$config->getBoolean('app.test.open'));
    }

    // ============================================================
    //  getTestUsers()
    // ============================================================

    public function testGetTestUsers(): void
    {
        $this->assertEquals(['token1', 'token2'], self::$config->getArray('app.test.tokens'));
    }

    // ============================================================
    //  getSuperAdminIds()
    // ============================================================

    public function testGetSuperAdminIds(): void
    {
        $this->assertEquals([1, 2, 1000], self::$config->getArray('app.superAdmin'));
    }


    // ============================================================
    //  getInt()
    // ============================================================

    public function testGetIntReturnsInt(): void
    {
        $this->assertSame(3306, self::$config->getInt('db.port'));
    }

    public function testGetIntReturnsDefaultForMissing(): void
    {
        $this->assertSame(0, self::$config->getInt('not.exists'));
        $this->assertSame(42, self::$config->getInt('not.exists', 42));
    }
}
