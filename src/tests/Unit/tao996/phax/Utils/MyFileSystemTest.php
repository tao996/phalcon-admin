<?php

namespace Tests\Unit\tao996\phax\Utils;

use Phax\Utils\MyFileSystem;

class MyFileSystemTest extends \PHPUnit\Framework\TestCase
{
    public function testFindInDirs()
    {
        $files = MyFileSystem::findInDirs(__DIR__);
        $basename = pathinfo(__FILE__, PATHINFO_BASENAME);
        $this->assertTrue(in_array($basename, $files));

        $files = MyFileSystem::findInDirs(dirname(__DIR__, 3));
        $this->assertTrue(in_array('TestModel.php', $files));
        $this->assertTrue(in_array('app', $files));
    }

    public function testFullpath()
    {
        $rst = MyFileSystem::fullpath('/abc', 'abc.php');
        $this->assertEquals('/abc/abc.php', $rst);
    }
}