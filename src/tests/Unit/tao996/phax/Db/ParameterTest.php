<?php

namespace Tests\Unit\tao996\phax\Db;

class ParameterTest extends \PHPUnit\Framework\TestCase
{
    public function testParameter()
    {
        $qb = new \Phax\Db\Parameter();

        $qb->columns(['id,name,title']);
        $this->assertEquals('id,name,title', $qb->getParameter()['columns']);

        $qb->columns('id,name');
        $this->assertEquals('id,name', $qb->getParameter()['columns']);

        $qb->like('name', 'pha');
        $this->assertEquals([
            "bind" => ["name" => "%pha%"],
            "bindTypes" => ["name" => 2],
            "conditions" => "name LIKE :name:",
            "columns" => "id,name"
        ], $qb->getParameter());
        $qb->like('age', 10);
        $this->assertEquals([
            "bind" => ["name" => "%pha%", "age" => "%10%"],
            "bindTypes" => ["name" => 2, "age" => 2],
            "conditions" => "name LIKE :name: AND (age LIKE :age:)",
            "columns" => "id,name"
        ], $qb->getParameter());


        $qbLikes = new \Phax\Db\Parameter();
        $qbLikes->likes('name', ['aaa', 'bbb']);
        $this->assertEquals([
            "bind" => [
                "name__0" => "%aaa%",
                "name__1" => "%bbb%"
            ],
            "bindTypes" => [
                "name__0" => 2,
                "name__1" => 2,
            ],
            "conditions" => "name LIKE :name__0: OR name LIKE :name__1:"
        ], $qbLikes->getParameter());

        $qbOrLike = new \Phax\Db\Parameter();
        $qbOrLike->orLike(['title', 'keyword'], 'hello');
        $this->assertEquals([
            "bind" => [
                "title" => "%hello%",
                "keyword" => "%hello%"
            ],
            "bindTypes" => [
                "title" => 2,
                "keyword" => 2,
            ],
            "conditions" => "title LIKE :title:  OR keyword LIKE :keyword: "
        ], $qbOrLike->getParameter());

        $rangeQb = new \Phax\Db\Parameter();
        $rangeQb->between('age', 10, 15);

        $this->assertEquals([
            "bind" => [10, 15],
            "bindTypes" => [1, 1],
            "conditions" => "age >= ?0 AND (age <= ?1)"
        ], $rangeQb->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->where('id1=5');
        $this->assertEquals('id1=5', $param->getParameter()['conditions']);

        $param->where(['id' => 1, 'age' => 6]);
        $this->assertEquals([
            'bind' => [1, 6],
            'bindTypes' => [1, 1],
            'conditions' => "id1=5 AND (id = ?0) AND (age = ?1)"
        ], $param->getParameter());

        $param->where('id2', 10)
            ->where('age2', [1, 2, 3])
            ->where('name', '=', 'bb');
        $this->assertEquals([
            'bind' => [1, 6, 10, 1, 2, 3, 'bb'],
            'bindTypes' => [1, 1, 1, 1, 1, 1, 2],
            'conditions' => "id1=5 AND (id = ?0) AND (age = ?1) AND (id2 = ?2) AND (age2 IN (?3,?4,?5)) AND (name = ?6)"
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->where("tag", ["a", "b", "c"]);
        $this->assertEquals([
            'bind' => ['a', 'b', 'c'],
            'bindTypes' => [2, 2, 2],
            'conditions' => 'tag IN (?0,?1,?2)',
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->opt("age", "=", 5)
            ->opt('name', 'like', 'aaa')
            ->opt('bb', '>=', 15);
        $this->assertEquals([
            'bind' => [5, '%aaa%', 15],
            'bindTypes' => [1, 2, 1],
            'conditions' => "age = ?0 AND (name like ?1) AND (bb >= ?2)"
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->int('age', 5)
            ->int('age', 0, skipEmpty: false);
        // int('age', 0) 现在不会跳过 0（0 是合法值）
        $this->assertEquals([
            'bind' => [5, 0],
            'bindTypes' => [1, 1],
            'conditions' => 'age = ?0 AND (age = ?1)'
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->in('age', [1, 2]);
        $this->assertEquals([
            'bind' => [1, 2],
            'bindTypes' => [1, 1],
            'conditions' => 'age IN (?0,?1)',
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->in("name", ['a', 'b']);
        $this->assertEquals([
            'bind' => ['a', 'b'],
            'bindTypes' => [2, 2],
            'conditions' => 'name IN (?0,?1)',
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->notEqual('id', 5);
        $this->assertEquals([
            'bind' => [5],
            'bindTypes' => [1],
            'conditions' => 'id != ?0'
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->where('id=5');
        $bb = $param->update(['age' => 10, 'name' => 5], 'abc');
        $this->assertEquals([
            'sql' => "UPDATE abc SET age = ?0,name = ?1 WHERE id=5",
            'bind' => [10, 5],
            'bindTypes' => [1, 1]
        ], $bb);

        $bb = $param->update('age=age+1', 'abc');
        $this->assertEquals([
            'sql' => "UPDATE abc SET age=age+1 WHERE id=5",
            'bind' => [],
            'bindTypes' => []
        ], $bb);

        $param = new \Phax\Db\Parameter();
        $param->where('id', 'in', [1, 2, 3]);
        $this->assertEquals([
            'bind' => [1, 2, 3],
            'bindTypes' => [1, 1, 1],
            'conditions' => "id IN (?0,?1,?2)",
        ], $param->getParameter());

        $param = new \Phax\Db\Parameter();
        $param->where(['id' => 1, 'name' => 'jj', 'age' => [1, 2, 3], 'ha' => ['a', 'b', 'c']]);
        $this->assertEquals([
            "bind" => [1, "jj", 1, 2, 3, 'a', 'b', 'c'],
            "bindTypes" => [1, 2, 1, 1, 1, 2, 2, 2],
            "conditions" => 'id = ?0 AND (name = ?1) AND (age IN (?2,?3,?4)) AND (ha IN (?5,?6,?7))',
        ], $param->getParameter());
    }

    public function testIntWithZero(): void
    {
        // int() 不应跳过 0
        $param = new \Phax\Db\Parameter();
        $param->int('status', 0, skipEmpty: false);
        $this->assertEquals([
            'bind' => [0],
            'bindTypes' => [1],
            'conditions' => 'status = ?0',
        ], $param->getParameter());
    }

    public function testIntWithNull(): void
    {
        // int() 应跳过 null（skipEmpty 默认 true）
        $param = new \Phax\Db\Parameter();
        $param->int('status', null);
        $this->assertEmpty($param->getParameter()['conditions'] ?? '');
    }

    public function testIntWithSkipEmptyFalse(): void
    {
        // int() 跳过空字符串（skipEmpty 默认 true）
        $param = new \Phax\Db\Parameter();
        $param->int('status', '');
        $this->assertEmpty($param->getParameter()['conditions'] ?? '');
    }

    public function testNotIn(): void
    {
        $param = new \Phax\Db\Parameter();
        $param->notIn('id', [3, 4, 5]);
        $this->assertEquals([
            'bind' => [3, 4, 5],
            'bindTypes' => [1, 1, 1],
            'conditions' => 'id NOT IN (?0,?1,?2)',
        ], $param->getParameter());
    }

    public function testNotInWithStrings(): void
    {
        $param = new \Phax\Db\Parameter();
        $param->notIn('name', ['x', 'y']);
        $this->assertEquals([
            'bind' => ['x', 'y'],
            'bindTypes' => [2, 2],
            'conditions' => 'name NOT IN (?0,?1)',
        ], $param->getParameter());
    }

    public function testEmptyIn(): void
    {
        $param = new \Phax\Db\Parameter();
        $param->in('id', []);
        $this->assertEmpty($param->getParameter()['conditions'] ?? '');
    }
}