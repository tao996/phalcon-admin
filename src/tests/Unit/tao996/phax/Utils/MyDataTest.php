<?php

namespace Tests\Unit\tao996\phax\Utils;

use Phax\Utils\MyData;
use PHPUnit\Framework\TestCase;

class MyDataTest extends TestCase
{
    public function testFindWithPath()
    {
        $data = [
            'a' => [
                'b' => 'vb',
                'c' => [
                    'd' => 'd1'
                ]
            ]
        ];
        $res = MyData::findWithPath($data, 'a');
        $this->assertEquals($data['a'], $res);

        $res = MyData::findWithPath($data, 'a.b');
        $this->assertEquals($data['a']['b'], $res);

        $res = MyData::findWithPath($data, 'a.c.d');
        $this->assertEquals('d1', $res);

        $this->assertNull(MyData::findWithPath($data, 'a.b.c.e'));
    }

    public function testColumnMap()
    {
        $students = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
            ['id' => 3, 'name' => 'c']
        ];
        $map = MyData::columnMap($students, 'id');
        $this->assertEquals(
            [1 => ['id' => 1, 'name' => 'a'], 2 => ['id' => 2, 'name' => 'b'], 3 => ['id' => 3, 'name' => 'c']],
            $map
        );

        $this->assertEquals($students, MyData::columnMap($students, null));
        $this->assertEquals([], MyData::columnMap($students, ''));
        try {
            $this->assertEquals([], MyData::columnMap($students, 0));
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('not exits', $e->getMessage());
        }
        $this->assertEquals([], MyData::columnMap($students, 0, true));
    }

    public function testGet()
    {
        $data = ['a' => 1];
        $this->assertEquals(1, MyData::get($data, 'a'));
        $this->assertEquals(5, MyData::get($data, 'b', 5));
        $this->assertEquals(5, MyData::get($data, '', 5));
        $this->assertEquals(5, MyData::get($data, null, 5));
        $this->assertEquals(5, MyData::get($data, 0, 5));
    }

    public function testGetInts()
    {
        $data = [
            'id1' => ["1", "2", "3"],
            'ids' => '1,2,3',
            'ida' => [1 => 'on', 2 => 'on', 3 => 'on'],
            'id2' => [1, 2, 3]
        ];
        $rst = [1, 2, 3];
        foreach (['ids', 'ida', 'id2'] as $key) {
            $ans = MyData::getIntsWith($data, $key);
            $this->assertEquals($rst, $ans);
        }
    }

    public function testGetBool()
    {
        foreach ([true, 'on', 'true', 't', 'ok', 1, '1'] as $v) {
            $this->assertTrue(MyData::isBool($v));
        }
        foreach (['on', 'true', 't', 'ok', 1, '1'] as $v) {
            $this->assertFalse(MyData::isBool($v, true), $v . ' is not strict bool');
        }
    }

    public function testNotEmpty()
    {
        $data = ['a', 'n' => 'name'];
        try {
            MyData::mustHasSet($data, ['n']);
        } catch (\Exception $e) {
            $this->assertTrue(false);
        }
        try {
            MyData::mustHasSet(['a', 'b' => 0], ['b']);
            $this->assertTrue(false);
        } catch (\Exception $e) {
        }
        $this->assertTrue(true);
    }

    public function testGetByKeys()
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 'hello'];
        $rst = MyData::getByKeys($data, ['a', 'c']);
        $this->assertEquals(['a' => 1, 'c' => 'hello'], $rst);
    }
}