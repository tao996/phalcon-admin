<?php

namespace Tests\Unit\tao996\phax\Db;


use PHPUnit\Framework\TestCase;


class LayerTest extends TestCase
{
    public function testLayer()
    {

        /**
         * @var \TestUser $user
         */
        $user = \TestUser::queryBuilder()->int('id', 1)->findFirstModel();
        if (empty($user)) {
            $user = new \TestUser();
            $user->assign([
                'name' => 'test',
                'age' => 1
            ]);
            $this->assertNotFalse($user->save());
        }
        $this->assertTrue($user->id > 0);

        $rst = \TestUser::layer()->incr('age', ['id' => 1], 5);
        $this->assertTrue($rst);
        $this->assertTrue(
            \TestUser::queryBuilder()
                ->int('id', 1)->int('age', $user->age + 5)->exits()
        );

        $rst = \TestUser::layer()->desc('age', ['id' => 1], 4);
        $this->assertTrue($rst);
        $this->assertTrue(
            \TestUser::queryBuilder()
                ->int('id', 1)->int('age', $user->age + 1)->exits()
        );

        $afterUser = \TestUser::findFirst(1);
        $this->assertEquals($user->age + 1, $afterUser->age);


        $name = time() . 'test' . rand(1, 10000);
        $rst = \TestUser::layer()->insert([
            'name' => $name,
            'age' => 10
        ]);
        $this->assertTrue($rst);
        $this->assertEquals(1, \TestUser::queryBuilder()->string('name', $name)->count());

        $newName = $name . '_aaa';
        $rst = \TestUser::layer()->update(['name' => $newName], ['name' => $name]);
        $this->assertTrue($rst);
        $this->assertEquals(
            1,
            \TestUser::queryBuilder()->string('name', $newName)->count()
        );

        $rst = \TestUser::layer()->delete(['name' => $newName]);
        $this->assertTrue($rst);
        $this->assertEquals(
            0,
            \TestUser::queryBuilder()->string('name', $newName)->count()
        );
    }
}