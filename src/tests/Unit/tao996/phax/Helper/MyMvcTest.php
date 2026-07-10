<?php

namespace Tests\Unit\tao996\phax\Helper;

use Phax\Foundation\Application;
use Phax\Foundation\AppService;
use Phax\Foundation\Route;
use PHPUnit\Framework\TestCase;

class MyMvcTest extends TestCase
{

    protected function setUp(): void
    {
        $di = Application::di();

        $route = new class('/test', $di) extends Route {
            public function appOrigin(): string
            {
                return 'http://localhost:8071';
            }
        };
        $route->urlOptions['language'] = 'en';

        $di->setShared('route', $route);
    }

    public function testUrlModuleWithApi(): void
    {
        $url = AppService::urlModule('a/b/c', true);
        $this->assertEquals('http://localhost:8071/en/api/m/a/b/c', $url);
    }

    public function testUrlModuleWithoutOrigin(): void
    {
        $url = AppService::urlModule('a/b/c', false);
        $this->assertEquals('/en/m/a/b/c', $url);
    }

    public function testUrlModuleWithQuery(): void
    {
        $url = AppService::urlModule('a/b/c', ['page' => 1]);
        $this->assertEquals('http://localhost:8071/en/m/a/b/c?page=1', $url);
    }

    public function testUrlProjectWithApi(): void
    {
        $url = AppService::urlProject('house', true);
        $this->assertEquals('http://localhost:8071/en/api/p/house', $url);
    }

    public function testUrlProjectWithoutOrigin(): void
    {
        $url = AppService::urlProject('house', false);
        $this->assertEquals('/en/p/house', $url);
    }

    public function testUrlProjectWithQuery(): void
    {
        $url = AppService::urlProject('house', ['id' => 5]);
        $this->assertEquals('http://localhost:8071/en/p/house?id=5', $url);
    }

    public function testUrlWith(): void
    {
        $url = AppService::urlWith('/search', ['q' => 'test']);
        $this->assertEquals('http://localhost:8071/en/search?q=test', $url);
    }

    public function testUrlWithNoQuery(): void
    {
        $url = AppService::urlWith('/about');
        $this->assertEquals('http://localhost:8071/en/about', $url);
    }
}