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

        $path = MyUrl::createWith(['path' => '/auth',
            'api' => true,
            'language' => 'en',
            'origin' => 'https://test.com'
        ]);
        $this->assertEquals('https://test.com/en/api/auth', $path);

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