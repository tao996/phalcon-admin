<?php
namespace Tests\Unit\tao996\phax\Helper;

use Phax\Helper\MyUrl;
use PHPUnit\Framework\TestCase;

class MyUrlTest extends TestCase
{
    public function testMyUrl()
    {
        $this->assertEquals('/auth', MyUrl::createWith(['path' => '/auth']));
        $this->assertEquals('/auth', MyUrl::createWith(['path' => 'auth']));

        $path = MyUrl::createWith(['path' => 'auth', 'module'=>true]);
        $this->assertEquals('/m/auth', $path);

        $path = MyUrl::createWith(['path' => 'auth', 'module'=>true, 'api' => true]);
        $this->assertEquals('/api/m/auth', $path);

        $path = MyUrl::createWith(['path' => 'auth', 'module'=>true, 'api' => true, 'language' => 'en']);
        $this->assertEquals('/en/api/m/auth', $path);

        $path = MyUrl::createWith([
            'path' => '/auth',
            'api' => true,
            'language' => 'en',
            'origin' => 'https://test.com'
        ]);
        $this->assertEquals('https://test.com/en/api/auth', $path);

        $appURL = MyUrl::createAppURL('abc', ['name' => 'j'], 'https://test.com');
        $this->assertEquals('https://test.com/p/abc?name=j', $appURL);

        $appApiURL = MyUrl::createAppApiURL('abc', ['name' => 'j'], 'https://test.com');
        $this->assertEquals('https://test.com/api/p/abc?name=j', $appApiURL);

        $mURL = MyUrl::createMultiURL('/m1/c1/a1', ['name' => 'j'], 'https://test.com');
        $this->assertEquals('https://test.com/m/m1/c1/a1?name=j', $mURL);

        $mApiURL = MyUrl::createMultiApiURL('/m1/c1/a1', ['name' => 'j'], 'https://test.com');
        $this->assertEquals('https://test.com/api/m/m1/c1/a1?name=j', $mApiURL);

        foreach (
            [
                'https://www.test.com/abc',
                'https://www.test.com',
            ] as $url
        ) {
            $this->assertTrue(
                MyUrl::inHosts($url, ['www.test.com'])
            );
        }

        foreach (
            [
                'https://test.com/abc',
                'https://www.test.co',
            ] as $url
        ) {
            $this->assertFalse(
                MyUrl::inHosts($url, ['www.test.com'])
            );
        }
    }
}