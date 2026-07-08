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

    public function testProjectNameReturnsDefault(): void
    {
        $this->assertEquals('defaultProject', self::$config->projectName());
    }

    public function testProjectConfigReturnsDefaultsNoHost(): void
    {
        $result = self::$config->projectConfig();
        $this->assertIsArray($result);
        $this->assertEquals('defaultProject', $result['name']);
        $this->assertStringContainsString('defaultProject', $result['namespace']);
        $this->assertStringContainsString('defaultProject', $result['viewpath']);
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

    // ============================================================
    //  projectName() / projectConfig() — edge cases
    // ============================================================

    public function testProjectNameReturnsEmptyWhenDefaultMissing(): void
    {
        $ref = new \ReflectionClass(\Phax\Support\Config::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $original = $prop->getValue(null);

        $prop->setValue(null, new \Phalcon\Config\Config([
            'app' => ['title' => 'no default'],
        ]));

        $this->assertEquals('', self::$config->projectName());

        $result = self::$config->projectConfig();
        $this->assertEquals('', $result['name']);
        $this->assertEquals('', $result['namespace']);
        $this->assertEquals('', $result['viewpath']);

        $prop->setValue(null, $original);
        $prop->setAccessible(false);
    }

    public function testProjectConfigFromActiveProject(): void
    {
        $ref = new \ReflectionClass(\Phax\Support\Config::class);
        $prop = $ref->getProperty('activeProject');
        $prop->setAccessible(true);
        $prop->setValue(self::$config, 'customProject');
        $prop->setAccessible(false);

        $result = self::$config->projectConfig();
        $this->assertEquals('customProject', $result['name']);
        $this->assertEquals('App\\Projects\\customProject\\Controllers', $result['namespace']);
        $this->assertStringContainsString('customProject', $result['viewpath']);

        $this->assertEquals('customProject', self::$config->projectName());

        $prop->setAccessible(true);
        $prop->setValue(self::$config, '');
        $prop->setAccessible(false);
    }

    // ============================================================
    //  resolveProjectConfig (private, via reflection)
    // ============================================================

    public function testResolveProjectConfigSimpleFormat(): void
    {
        $ref = new \ReflectionClass(\Phax\Support\Config::class);
        $method = $ref->getMethod('resolveProjectConfig');
        $method->setAccessible(true);

        $result = $method->invoke(self::$config, 'myProject');
        $this->assertEquals('myProject', $result['name']);
        $this->assertEquals('App\\Projects\\myProject\\Controllers', $result['namespace']);
        $this->assertStringContainsString('myProject', $result['viewpath']);
    }

    public function testResolveProjectConfigExtendedFormat(): void
    {
        $ref = new \ReflectionClass(\Phax\Support\Config::class);
        $method = $ref->getMethod('resolveProjectConfig');
        $method->setAccessible(true);

        $result = $method->invoke(self::$config, 'myProject', [
            'domains' => ['my.test.com'],
            'namespace' => 'Custom\\Ns',
            'viewpath' => '/custom/path',
        ]);
        $this->assertEquals('myProject', $result['name']);
        $this->assertEquals('Custom\\Ns', $result['namespace']);
        $this->assertEquals('/custom/path', $result['viewpath']);
    }

    public function testResolveProjectConfigExtendedPartial(): void
    {
        $ref = new \ReflectionClass(\Phax\Support\Config::class);
        $method = $ref->getMethod('resolveProjectConfig');
        $method->setAccessible(true);

        $result = $method->invoke(self::$config, 'partialProj', [
            'domains' => ['partial.test.com'],
        ]);
        $this->assertEquals('partialProj', $result['name']);
        $this->assertEquals('App\\Projects\\partialProj\\Controllers', $result['namespace']);
        $this->assertStringContainsString('partialProj', $result['viewpath']);
    }
}
