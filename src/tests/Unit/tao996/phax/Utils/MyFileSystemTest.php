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

    public function testFilterFilesByGitignore()
    {
        $ignoreContent = <<< ABC
Bridge
Db
Facade
Helper
Support
ABC;
        $patterns = MyFileSystem::generateFilterPatternsByGitignore($ignoreContent);
        $rst = MyFileSystem::filterByGitignorePatterns('Bridge/abc.txt', $patterns);
        $this->assertTrue($rst);

        $dirname = dirname(__DIR__) . '/';
        $files = MyFileSystem::getFilesInDirectory($dirname, function ($file) use ($dirname, $patterns) {
            return MyFileSystem::filterByGitignorePatterns($file, $patterns, $dirname);
        });
        $this->assertEquals(2, count($files));

        $patterns = MyFileSystem::generateFilterPatternsByGitignore(
            <<< ABC
*
!Utils
ABC
        );
        $rst = MyFileSystem::filterByGitignorePatterns('Utils/abc.txt', $patterns);
        $this->assertFalse($rst);

        $rst = MyFileSystem::filterByGitignorePatterns('Bridge/abc.txt', $patterns);
        $this->assertTrue($rst);
        $files2 = MyFileSystem::getFilesInDirectory($dirname, function ($file) use ($dirname, $patterns) {
            return MyFileSystem::filterByGitignorePatterns($file, $patterns, $dirname);
        });
        $this->assertEquals($files, $files2);
    }
}