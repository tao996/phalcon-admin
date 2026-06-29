<?php

namespace Tests\Unit\tao996\phax\Db;

use Phax\Db\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    public function testGetRawPdoSqlNamed(): void
    {
        // getRawPdoSql 做简单替换，不加引号；每个占位符最多替换一次（limit=1）
        $sql = 'SELECT * FROM users WHERE id = :id AND name = :name';
        $result = Transaction::getRawPdoSql($sql, [':id' => 5, ':name' => 'Alice']);
        $this->assertEquals("SELECT * FROM users WHERE id = 5 AND name = Alice", $result);
    }

    public function testGetRawPdoSqlPositional(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ? AND name = ?';
        $result = Transaction::getRawPdoSql($sql, [1, 'Bob']);
        $this->assertEquals("SELECT * FROM users WHERE id = 1 AND name = Bob", $result);
    }

    public function testGetRawPdoSqlWithoutParams(): void
    {
        $sql = 'SELECT * FROM users';
        $result = Transaction::getRawPdoSql($sql);
        $this->assertEquals('SELECT * FROM users', $result);
    }

    public function testGetRawPdoSqlBackrefInjection(): void
    {
        $sql = 'SELECT * FROM t WHERE val = :val';

        // $1 不会被当作反向引用
        $result = Transaction::getRawPdoSql($sql, [':val' => '$1']);
        $this->assertEquals("SELECT * FROM t WHERE val = \$1", $result);

        // \0 不会被当作反向引用
        $result2 = Transaction::getRawPdoSql($sql, [':val' => '\\0']);
        $this->assertEquals("SELECT * FROM t WHERE val = \\0", $result2);
    }

    public function testGetRawPdoSqlDuplicateNamed(): void
    {
        // 注意：limit=1 只替换第一个同名占位符，第二个保持原样
        $sql = 'SELECT * FROM t WHERE a = :x OR b = :x';
        $result = Transaction::getRawPdoSql($sql, [':x' => 'foo']);
        $this->assertEquals("SELECT * FROM t WHERE a = foo OR b = :x", $result);
    }

    public function testGetRawPdoSqlMixedTypes(): void
    {
        $sql = 'SELECT * FROM t WHERE id = ? AND name = :name AND age = ?';
        $result = Transaction::getRawPdoSql($sql, [10, ':name' => 'test', 25]);
        $this->assertEquals("SELECT * FROM t WHERE id = 10 AND name = test AND age = 25", $result);
    }
}
