<?php
namespace Tests\Unit\tao996\phax\Helper;

use Phax\Foundation\AppService;
use Phax\Foundation\Context\RouteMatchContext;
use Phax\Utils\MyUrlBuilder;
use PHPUnit\Framework\TestCase;

class MyUrlTest extends TestCase
{

    // ─── MyUrlBuilder — 覆盖 MyUrl::createWith() 的等价场景 ───────────

    public function testBuilderBasicPath(): void
    {
        $this->assertEquals('/auth', MyUrlBuilder::new()->path('/auth')->build());
        $this->assertEquals('/auth', MyUrlBuilder::new()->path('auth')->build());
    }

    public function testBuilderWithModule(): void
    {
        $url = MyUrlBuilder::new()->withModule('auth')->build();
        $this->assertEquals('/m/auth', $url);
    }

    public function testBuilderWithModuleAndApi(): void
    {
        $url = MyUrlBuilder::new()->withModule('auth')->asApi()->build();
        $this->assertEquals('/api/m/auth', $url);
    }

    public function testBuilderFullChain(): void
    {
        $url = MyUrlBuilder::new()
            ->language('en')
            ->asApi()
            ->withModule('auth')
            ->build();
        $this->assertEquals('/en/api/m/auth', $url);
    }

    public function testBuilderWithOrigin(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/auth')
            ->asApi()
            ->language('en')
            ->origin('https://test.com')
            ->build();
        $this->assertEquals('https://test.com/en/api/auth', $url);
    }

    public function testBuilderQueryArray(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/search')
            ->queryParams(['q' => 'test', 'page' => 2])
            ->build();
        $this->assertEquals('/search?q=test&page=2', $url);
    }

    public function testBuilderQueryString(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/search')
            ->queryParams('q=test&page=2')
            ->build();
        $this->assertEquals('/search?q=test&page=2', $url);
    }

    public function testBuilderWithProject(): void
    {
        $url = MyUrlBuilder::new()->withProject('house')->build();
        $this->assertEquals('/p/house', $url);
    }

    public function testBuilderLanguageOnly(): void
    {
        $url = MyUrlBuilder::new()->language('zh-CN')->path('/home')->build();
        $this->assertEquals('/zh-CN/home', $url);
    }

    public function testBuilderApiOnly(): void
    {
        $url = MyUrlBuilder::new()->asApi()->path('/users')->build();
        $this->assertEquals('/api/users', $url);
    }

    public function testBuilderWithExisingQuery(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/list')
            ->queryParams('page=1')
            ->build();
        $this->assertEquals('/list?page=1', $url);
    }

    public function testBuilderOriginNoTrailingSlash(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/home')
            ->origin('https://site.com')
            ->build();
        $this->assertEquals('https://site.com/home', $url);
    }

    public function testBuilderOriginWithTrailingSlash(): void
    {
        $url = MyUrlBuilder::new()
            ->path('/home')
            ->origin('https://site.com/')
            ->build();
        $this->assertEquals('https://site.com/home', $url);
    }

    // ─── url() 使用 MyUrlBuilder 后的集成测试 ────────────────

    protected function setUpMyMvc(): void
    {
        $di = \Phax\Foundation\Application::di();

        // 使用匿名类手动构造 route stub，避免 mock 在 Phalcon DI 中的兼容问题
        $rc = new class('/test') extends RouteMatchContext {
            public function appOrigin(): string
            {
                return 'http://localhost:8071';
            }
        };
        $rc->language = 'en';

        $di->setShared('context', $rc);
    }

    public static function urlProvider(): array
    {
        return [
            'basic path'                   => [['path' => '/auth'],                       'http://localhost:8071/en/auth'],
            'basic path without slash'     => [['path' => 'auth'],                        'http://localhost:8071/en/auth'],
            'with module'                  => [['path' => 'auth', 'module' => true],       'http://localhost:8071/en/m/auth'],
            'with module and api'          => [['path' => 'auth', 'module' => true, 'api' => true], 'http://localhost:8071/en/api/m/auth'],
            'with project'                 => [['path' => 'house', 'project' => true],     'http://localhost:8071/en/p/house'],
            'with query array'             => [['path' => '/search', 'query' => ['q' => 'test']], 'http://localhost:8071/en/search?q=test'],
            'with query string'            => [['path' => '/search', 'query' => 'q=test'], 'http://localhost:8071/en/search?q=test'],
            'module with query'            => [['path' => 'list', 'module' => true, 'query' => ['page' => 1]], 'http://localhost:8071/en/m/list?page=1'],
            'api only'                     => [['path' => '/users', 'api' => true],        'http://localhost:8071/en/api/users'],
            'origin empty string'          => [['path' => '/auth', 'origin' => ''],        '/en/auth'],
            'custom origin'                => [['path' => '/auth', 'api' => true, 'origin' => 'https://test.com'], 'https://test.com/en/api/auth'],
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrlWithBuilder(array $options, string $expected): void
    {
        $this->setUpMyMvc();
        $this->assertEquals($expected, AppService::url($options));
    }
}