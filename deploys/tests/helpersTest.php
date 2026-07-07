<?php

use PHPUnit\Framework\TestCase;

class helpersTest extends TestCase
{
    /* ---------------- array_get ---------------- */

    public function testArrayGetSimpleKey(): void
    {
        $data = ['name' => 'phalcon', 'version' => '5.0'];
        $this->assertEquals('phalcon', array_get($data, 'name'));
        $this->assertEquals('5.0', array_get($data, 'version'));
    }

    public function testArrayGetNestedKey(): void
    {
        $data = [
            'app' => [
                'title' => 'My App',
                'jwt' => ['secret' => 's3cret'],
            ],
        ];
        $this->assertEquals('My App', array_get($data, 'app.title'));
        $this->assertEquals('s3cret', array_get($data, 'app.jwt.secret'));
    }

    public function testArrayGetDefaultValue(): void
    {
        $data = ['name' => 'phalcon'];
        $this->assertNull(array_get($data, 'nonexistent'));
        $this->assertEquals('default', array_get($data, 'nonexistent', 'default'));
        $this->assertEquals([], array_get($data, 'nested.missing', []));
    }

    public function testArrayGetReturnsNullForMissingNested(): void
    {
        $data = ['app' => ['title' => 'test']];
        $this->assertNull(array_get($data, 'app.nonexistent'));
        $this->assertNull(array_get($data, 'app.title.nested'));
    }

    public function testArrayGetWithEmptyArray(): void
    {
        $this->assertNull(array_get([], 'anything'));
        $this->assertEquals(42, array_get([], 'anything', 42));
    }

    /* ---------------- safe_name ---------------- */

    public function testSafeNameKeepsAlphanumeric(): void
    {
        $this->assertEquals('myapp', safe_name('myapp'));
        $this->assertEquals('demo123', safe_name('demo123'));
    }

    public function testSafeNameReplacesSpecialChars(): void
    {
        $this->assertEquals('my-app', safe_name('my-app'));
        $this->assertEquals('my_app', safe_name('my_app'));
    }

    public function testSafeNameStripsInvalidChars(): void
    {
        $this->assertEquals('myapp', safe_name('my.app'));
        $this->assertEquals('myapp', safe_name('my app!'));
        $this->assertEquals('abc', safe_name('a@b#c$'));
    }

    public function testSafeNameWithEmptyString(): void
    {
        $this->assertEquals('', safe_name(''));
    }

    /* ---------------- array_merge_deep ---------------- */

    public function testMergeDeepSimple(): void
    {
        $a = ['name' => 'a', 'version' => 1];
        $b = ['name' => 'b'];
        $result = array_merge_deep($a, $b);
        $this->assertEquals('b', $result['name']);
        $this->assertEquals(1, $result['version']);
    }

    public function testMergeDeepNested(): void
    {
        $a = ['ssh' => ['host' => '1.2.3.4', 'port' => 22]];
        $b = ['ssh' => ['user' => 'root']];
        $result = array_merge_deep($a, $b);
        $this->assertEquals('1.2.3.4', $result['ssh']['host']);
        $this->assertEquals(22, $result['ssh']['port']);
        $this->assertEquals('root', $result['ssh']['user']);
    }

    public function testMergeDeepOverrideNested(): void
    {
        $a = ['ssh' => ['host' => 'old', 'port' => 22]];
        $b = ['ssh' => ['host' => 'new']];
        $result = array_merge_deep($a, $b);
        $this->assertEquals('new', $result['ssh']['host']);
        $this->assertEquals(22, $result['ssh']['port']);
    }

    public function testMergeDeepWithThreeArrays(): void
    {
        $a = ['a' => 1];
        $b = ['b' => 2];
        $c = ['c' => 3];
        $result = array_merge_deep($a, $b, $c);
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }

    public function testMergeDeepEmptyOverridesNothing(): void
    {
        $a = ['key' => 'value'];
        $result = array_merge_deep($a, []);
        $this->assertEquals(['key' => 'value'], $result);

        $result = array_merge_deep([], $a);
        $this->assertEquals(['key' => 'value'], $result);
    }

    public function testMergeDeepWithMultipleArraysOverride(): void
    {
        $a = ['level' => ['nested' => 'from_a', 'keep' => 'stay']];
        $b = ['level' => ['nested' => 'from_b', 'extra' => 'new']];
        $c = ['level' => ['nested' => 'from_c']];
        $result = array_merge_deep($a, $b, $c);
        $this->assertEquals('from_c', $result['level']['nested']);
        $this->assertEquals('stay', $result['level']['keep']);
        $this->assertEquals('new', $result['level']['extra']);
    }
}
