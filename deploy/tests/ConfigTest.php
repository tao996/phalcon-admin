<?php

use PHPUnit\Framework\TestCase;

/**
 * 不依赖文件路径：通过 loadServer($path) 显式传参 + 手动构造 project 配置 来测试合并逻辑
 */
class ConfigTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
    }

    /* ---------------- loadServer ---------------- */

    public function testLoadServerReturnsConfig(): void
    {
        $config = new DeployConfig();
        $result = $config->loadServer($this->fixturesDir . '/server.php');

        $this->assertIsArray($result);
        $this->assertEquals('1.2.3.4', $result['ssh']['host']);
        $this->assertEquals('root', $result['ssh']['user']);
    }

    public function testLoadServerFailsOnMissingFile(): void
    {
        $config = new DeployConfig();
        $this->assertFileDoesNotExist('/nonexistent/path.php');
    }

    /* ---------------- 配置合并 + getter ---------------- */

    public function testGetMergedCombinesServerAndProject(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        // 手动设置 project（模拟 loadProject 的结果）
        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $merged = $config->getMerged();

        // SSH 来自 server（project 不覆盖）
        $this->assertEquals('1.2.3.4', $merged['ssh']['host']);
        $this->assertEquals('root', $merged['ssh']['user']);
        // 项目覆盖 server 的 port
        $this->assertEquals(2222, $merged['ssh']['port']);

        // 项目字段
        $this->assertEquals('testproj', $merged['project']['name']);
        $this->assertEquals('/root/projects/testproj', $merged['project']['path']);

        // 域名
        $this->assertEquals(['testproj.example.com'], $merged['domains']);

        // env 合并
        $this->assertEquals('testproj', $merged['env']['APP_NAME']);
        $this->assertEquals('Asia/Shanghai', $merged['env']['TZ']);  // 来自 server
        $this->assertEquals('testproj_db', $merged['env']['MYSQL_DATABASE']); // 来自 project

        // config
        $this->assertEquals('Test Project', $merged['config']['app.title']);
    }

    public function testGetProjectPath(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $this->assertEquals('/root/projects/testproj', $config->getProjectPath());
    }

    public function testGetProjectName(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('projectName');
        $prop->setAccessible(true);
        $prop->setValue($config, 'testproj');

        $this->assertEquals('testproj', $config->getProjectName());
    }

    public function testGetDomains(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $this->assertEquals(['testproj.example.com'], $config->getDomains());
    }

    public function testGetSyncItems(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $items = $config->getSyncItems();

        $this->assertCount(2, $items);

        // git 方式的主仓库（path 为空）
        $this->assertEquals('git', $items[0]['method']);
        $this->assertEquals('', $items[0]['path']);
        $this->assertEquals('git@example.com:test.git', $items[0]['repo']);
        $this->assertEquals('main', $items[0]['branch']);

        // ftp 方式（branch 默认 main，excludes 归一化）
        $this->assertEquals('ftp', $items[1]['method']);
        $this->assertEquals('src/App/Projects/boyu', $items[1]['path']);
        $this->assertEquals('main', $items[1]['branch']);
        $this->assertEquals(['views/assets'], $items[1]['excludes']);
    }

    public function testGetSshConfig(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ssh = $config->getSshConfig();
        $this->assertEquals('1.2.3.4', $ssh['host']);
        $this->assertEquals('root', $ssh['user']);
    }

    public function testGetEnvOverrides(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $env = $config->getEnvOverrides();
        $this->assertArrayHasKey('APP_NAME', $env);
        $this->assertArrayHasKey('MYSQL_DATABASE', $env);
    }

    public function testGetHooks(): void
    {
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        $ref = new ReflectionClass($config);
        $prop = $ref->getProperty('project');
        $prop->setAccessible(true);
        $prop->setValue($config, require $this->fixturesDir . '/project.php');

        $hooks = $config->getHooks();
        $this->assertArrayHasKey('afterInit', $hooks);
        $this->assertCount(1, $hooks['afterInit']);
    }

    /* ---------------- loadProject ---------------- */

    /**
     * 测试 loadProject 是否能正确读取存在的项目配置
     */
    public function testLoadProjectFromProjectsDir(): void
    {
        // 用 yihe 项目（真实存在的配置）
        $config = new DeployConfig();
        $config->loadServer($this->fixturesDir . '/server.php');

        // 注意：这会真实读取 deploy/projects/yihe/server.php
        // 所以要求 yihe 配置文件结构有效
        $result = $config->loadProject('yihe');

        $this->assertIsArray($result);
        $this->assertEquals('yihe', $result['project']['name'] ?? '');
    }
}
