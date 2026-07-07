<?php

use PHPUnit\Framework\TestCase;

class TemplateRendererTest extends TestCase
{
    private string $fixturesDir;
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures/templates';
        $this->renderer = new TemplateRenderer($this->fixturesDir);
    }

    /* ---------------- render (单个文件) ---------------- */

    public function testRenderSimplePlaceholder(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/simple.txt',
            ['NAME' => 'Phalcon', 'VERSION' => '5.0']
        );

        $this->assertStringContainsString('Hello Phalcon', $result);
        $this->assertStringContainsString('Version: 5.0', $result);
    }

    public function testRenderAllPlaceholdersReplaced(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/.env',
            [
                'APP_NAME' => 'myapp',
                'APP_ENV' => 'production',
                'DB_HOST' => 'mysql',
                'DB_NAME' => 'myapp_db',
                'DB_USER' => 'admin',
                'DB_PASS' => 'secret',
            ]
        );

        $this->assertStringContainsString('APP_NAME=myapp', $result);
        $this->assertStringContainsString('APP_ENV=production', $result);
        $this->assertStringContainsString('DB_HOST=mysql', $result);
        $this->assertStringContainsString('DB_NAME=myapp_db', $result);
        $this->assertStringContainsString('DB_USER=admin', $result);
        $this->assertStringContainsString('DB_PASS=secret', $result);
    }

    public function testRenderMissingPlaceholderKeptAsIs(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/simple.txt',
            ['NAME' => 'Phalcon'] // VERSION 未提供
        );

        $this->assertStringContainsString('Hello Phalcon', $result);
        // 未提供的占位符保持原样
        $this->assertStringContainsString('{{VERSION}}', $result);
    }

    public function testRenderNonexistentTemplate(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/nonexistent.txt',
            ['KEY' => 'value']
        );

        $this->assertEquals('', $result);
    }

    /* ---------------- renderToFile ---------------- */

    public function testRenderToFileCreatesFile(): void
    {
        $outputPath = sys_get_temp_dir() . '/deploy-test-' . uniqid() . '.txt';
        $this->renderer->renderToFile(
            $this->fixturesDir . '/simple.txt',
            $outputPath,
            ['NAME' => 'Test', 'VERSION' => '1.0']
        );

        $this->assertFileExists($outputPath);
        $content = file_get_contents($outputPath);
        $this->assertStringContainsString('Hello Test', $content);
        $this->assertStringContainsString('Version: 1.0', $content);

        unlink($outputPath);
    }

    public function testRenderToFileCreatesDirectory(): void
    {
        $tmpDir = sys_get_temp_dir() . '/deploy-test-' . uniqid();
        $outputPath = $tmpDir . '/sub/dir/output.txt';

        $this->renderer->renderToFile(
            $this->fixturesDir . '/simple.txt',
            $outputPath,
            ['NAME' => 'DirTest', 'VERSION' => '2.0']
        );

        $this->assertFileExists($outputPath);
        unlink($outputPath);
        // 从内向外逐级清理目录
        rmdir(dirname($outputPath));     // sub/dir
        rmdir(dirname(dirname($outputPath))); // sub
        rmdir($tmpDir);
    }

    /* ---------------- renderDir ---------------- */

    public function testRenderDirCopiesAllTemplates(): void
    {
        $tmpDir = sys_get_temp_dir() . '/deploy-test-' . uniqid();

        $this->renderer->renderDir('env-dir', $tmpDir, [
            'APP_NAME' => 'testapp',
            'DB_NAME' => 'testdb',
        ]);

        // .env 文件应该被渲染
        $envFile = $tmpDir . '/.env';
        $this->assertFileExists($envFile);
        $content = file_get_contents($envFile);
        $this->assertStringContainsString('APP_NAME=testapp', $content);
        $this->assertStringContainsString('DB_NAME=testdb', $content);

        // 清理
        unlink($envFile);
        rmdir($tmpDir);
    }

    public function testRenderDirSkipsUnderscoreFiles(): void
    {
        $tmpDir = sys_get_temp_dir() . '/deploy-test-' . uniqid();

        $this->renderer->renderDir('env-dir', $tmpDir, [
            'APP_NAME' => 'testapp',
        ]);

        // _internal.txt 不应该被渲染
        $this->assertFileDoesNotExist($tmpDir . '/_internal.txt');

        // .env 应该被渲染
        $this->assertFileExists($tmpDir . '/.env');

        unlink($tmpDir . '/.env');
        rmdir($tmpDir);
    }

    public function testRenderDirNonexistentSubDir(): void
    {
        $tmpDir = sys_get_temp_dir() . '/deploy-test-' . uniqid();

        // 应该不报错，只输出 warn
        $this->renderer->renderDir('nonexistent', $tmpDir, ['KEY' => 'val']);

        $this->assertDirectoryDoesNotExist($tmpDir);
    }

    /* ---------------- renderDir 子目录结构 ---------------- */

    public function testRenderDirMaintainsSubdirectoryStructure(): void
    {
        $tmpDir = sys_get_temp_dir() . '/deploy-test-' . uniqid();

        $this->renderer->renderDir('nested', $tmpDir, [
            'APP_NAME' => 'myapp',
            'ROOT' => '/var/www',
        ]);

        $this->assertFileExists($tmpDir . '/nginx/default.conf');
        $this->assertFileExists($tmpDir . '/php/php.ini');

        $nginxContent = file_get_contents($tmpDir . '/nginx/default.conf');
        $this->assertStringContainsString('server_name myapp', $nginxContent);

        $phpContent = file_get_contents($tmpDir . '/php/php.ini');
        $this->assertStringContainsString('memory_limit = 256M', $phpContent);

        // 清理
        unlink($tmpDir . '/nginx/default.conf');
        unlink($tmpDir . '/php/php.ini');
        rmdir($tmpDir . '/nginx');
        rmdir($tmpDir . '/php');
        rmdir($tmpDir);
    }

    /* ---------------- 边界情况 ---------------- */

    public function testRenderWithEmptyVars(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/simple.txt',
            []
        );

        // 占位符保持不变
        $this->assertStringContainsString('{{NAME}}', $result);
        $this->assertStringContainsString('{{VERSION}}', $result);
    }

    public function testRenderWithSpecialCharacters(): void
    {
        $result = $this->renderer->render(
            $this->fixturesDir . '/simple.txt',
            ['NAME' => 'test@#$%', 'VERSION' => '1.0-beta+2']
        );

        $this->assertStringContainsString('Hello test@#$%', $result);
        $this->assertStringContainsString('Version: 1.0-beta+2', $result);
    }

    public function testRenderNoPlaceholders(): void
    {
        // 创建一个没有占位符的临时文件
        $tmpFile = sys_get_temp_dir() . '/deploy-test-' . uniqid() . '.txt';
        file_put_contents($tmpFile, 'Plain text without placeholders.');

        $result = $this->renderer->render($tmpFile, ['ANY' => 'value']);
        $this->assertEquals('Plain text without placeholders.', $result);

        unlink($tmpFile);
    }
}
