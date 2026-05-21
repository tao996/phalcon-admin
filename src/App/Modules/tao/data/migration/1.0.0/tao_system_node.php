<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoSystemNodeMigration_100
 */
class TaoSystemNodeMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_system_node', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'unsigned' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 1,
                        'first' => true
                    ]
                ),
                new Column(
                    'kind',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'module',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "所属模块",
                        'after' => 'kind'
                    ]
                ),
                new Column(
                    'node',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "节点代码",
                        'after' => 'module'
                    ]
                ),
                new Column(
                    'title',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "节点标题",
                        'after' => 'node'
                    ]
                ),
                new Column(
                    'type',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "3",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "节点类型",
                        'after' => 'title'
                    ]
                ),
                new Column(
                    'ac',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "变化类型",
                        'after' => 'type'
                    ]
                ),
                new Column(
                    'is_auth',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "1",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "是否启动RBAC权限控制",
                        'after' => 'ac'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('node', ['node'], ''),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '151',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8mb4_0900_ai_ci',
            ],
        ]);
    }

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up(): void
    {
        $this->batchInsert('tao_system_node', [
            'id',
            'kind',
            'module',
            'node',
            'title',
            'type',
            'ac',
            'is_auth',
        ]);
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
        $this->batchDelete('tao_system_node');
    }
}
