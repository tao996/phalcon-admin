<?php

namespace Tests\Unit\tao996\phax\Db;

use Phax\Foundation\Application;
use Phax\Helper\MyMvc;
use TestUser;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testQueryBuilder()
    {
        $mvc = new MyMvc(Application::di());
        if (TestUser::queryBuilder()->where(['name' => 'test__a', 'age' => 1])->notExists()) {
            TestUser::layer()->batchInsert([
                ['name' => 'test__a', 'age' => 1],
                ['name' => 'test__b', 'age' => 6],
                ['name' => 'test__c', 'age' => 7],
            ]);
        }

        $rows = TestUser::queryBuilder()
            ->field(['id', 'created_at', 'name', 'age'])
            ->like('name', 'test__')
            ->find();
        $this->assertTrue(count($rows) >= 3);
        foreach ($rows as $row) {
            $this->assertEquals(0, $row['created_at']);
        }

        $this->assertEquals(
            2,
            TestUser::queryBuilder()
                ->likes('name', ['test__a', 'test__b'])
                ->count()
        );

        $firstRows = TestUser::queryBuilder()
            ->excludeColumns(['created_at', 'updated_at', 'deleted_at'])
            ->like('name', 'test__')
            ->find()[0];
        $this->assertFalse(isset($firstRows['created_at']));
        $this->assertFalse(isset($firstRows['updated_at']));
        $this->assertFalse(isset($firstRows['deleted_at']));

        $rows = TestUser::queryBuilder()
            ->like('name', 'test__')
            ->orderBy('id desc')->find();
        $this->assertTrue($rows[0]['id'] > $rows[1]['id']);

        $this->assertEquals(
            1,
            count(
                TestUser::queryBuilder()
                    ->like('name', 'test__')
                    ->pagination(0, 1)->find()
            )
        );

        $exception = null;
        $qb = TestUser::queryBuilder()->int('id', 1);
        try {
            // test update before save
            $qb->update(['age' => '-1']);
            $this->assertTrue(false, 'should not run here');
        } catch (\Exception $e) {
            $exception = true;
            $this->assertStringContainsString('sorry', $e->getMessage());
        }
        $this->assertTrue($exception);
        /**
         * @var $user TestUser
         */
        $user = $qb->findFirstModel();
        $this->assertEquals(1, $user->id);
        $user->age = 10000;
        $this->assertTrue($user->updateColumns('age'));
    }

}