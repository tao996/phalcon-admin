<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoSystemMenuMigration_100
 */
class TaoSystemMenuMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_system_menu', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'unsigned' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 1,
                        'first' => true
                    ]
                ),
                new Column(
                    'href',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "链接",
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'params',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 500,
                        'comment' => "链接参数",
                        'after' => 'href'
                    ]
                ),
                new Column(
                    'sort',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "菜单排序",
                        'after' => 'params'
                    ]
                ),
                new Column(
                    'status',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "1",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "状态(0:禁用,1:启用)",
                        'after' => 'sort'
                    ]
                ),
                new Column(
                    'type',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "1",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "多模块",
                        'after' => 'status'
                    ]
                ),
                new Column(
                    'roles',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "指定访问角色",
                        'after' => 'type'
                    ]
                ),
                new Column(
                    'remark',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'after' => 'roles'
                    ]
                ),
                new Column(
                    'pid',
                    [
                        'type' => Column::TYPE_BIGINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "父id",
                        'after' => 'remark'
                    ]
                ),
                new Column(
                    'title',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "名称",
                        'after' => 'pid'
                    ]
                ),
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "创建时间",
                        'after' => 'title'
                    ]
                ),
                new Column(
                    'updated_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "更新时间",
                        'after' => 'created_at'
                    ]
                ),
                new Column(
                    'deleted_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'notNull' => false,
                        'size' => 1,
                        'comment' => "删除时间",
                        'after' => 'updated_at'
                    ]
                ),
                new Column(
                    'icon',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "菜单图标",
                        'after' => 'deleted_at'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('title', ['title'], ''),
                new Index('href', ['href'], ''),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '28',
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
        $this->batchInsert('tao_system_menu', [
            'id',
            'href',
            'params',
            'sort',
            'status',
            'type',
            'roles',
            'remark',
            'pid',
            'title',
            'created_at',
            'updated_at',
            'deleted_at',
            'icon',
        ]);
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
        $this->batchDelete('tao_system_menu');
    }
}
