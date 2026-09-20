<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoSystemUserLoginMigration_100
 */
class TaoSystemUserLoginMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_system_user_login', [
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
                    'user_id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "用户ID",
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'token',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "登录token",
                        'after' => 'user_id'
                    ]
                ),
                new Column(
                    'secret',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "签名密钥",
                        'after' => 'token'
                    ]
                ),
                new Column(
                    'kind',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "app",
                        'notNull' => true,
                        'size' => 20,
                        'comment' => "类型(app/web)",
                        'after' => 'secret'
                    ]
                ),
                new Column(
                    'ip',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "登录IP",
                        'after' => 'kind'
                    ]
                ),
                new Column(
                    'useragent',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "客户端信息",
                        'after' => 'ip'
                    ]
                ),
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "创建时间",
                        'after' => 'useragent'
                    ]
                ),
                new Column(
                    'updated_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "更新时间",
                        'after' => 'created_at'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('user_id', ['user_id'], ''),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '1',
                'ENGINE' => 'InnoDB',
                'TABLE_COLLATION' => 'utf8mb4_0900_ai_ci',
                'TABLE_COMMENT' => '用户登录记录',
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
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
    }
}
