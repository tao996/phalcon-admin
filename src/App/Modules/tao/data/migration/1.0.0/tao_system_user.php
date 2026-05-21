<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoSystemUserMigration_100
 */
class TaoSystemUserMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_system_user', [
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
                    'created_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "创建时间",
                        'after' => 'id'
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
                    'status',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "1",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "状态(0:禁用,1:启用,)",
                        'after' => 'deleted_at'
                    ]
                ),
                new Column(
                    'role_ids',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "角色权限ID",
                        'after' => 'status'
                    ]
                ),
                new Column(
                    'seed',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 10,
                        'comment' => "随机数",
                        'after' => 'role_ids'
                    ]
                ),
                new Column(
                    'password',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "用户登录密码",
                        'after' => 'seed'
                    ]
                ),
                new Column(
                    'email',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 30,
                        'comment' => "邮箱",
                        'after' => 'password'
                    ]
                ),
                new Column(
                    'email_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "邮箱修改时间",
                        'after' => 'email'
                    ]
                ),
                new Column(
                    'email_valid',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'email_at'
                    ]
                ),
                new Column(
                    'phone',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 16,
                        'comment' => "联系手机号",
                        'after' => 'email_valid'
                    ]
                ),
                new Column(
                    'phone_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "手机号修改时间",
                        'after' => 'phone'
                    ]
                ),
                new Column(
                    'phone_valid',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'phone_at'
                    ]
                ),
                new Column(
                    'nickname',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 20,
                        'comment' => "用户昵称",
                        'after' => 'phone_valid'
                    ]
                ),
                new Column(
                    'head_img',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "头像",
                        'after' => 'nickname'
                    ]
                ),
                new Column(
                    'signature',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "签名",
                        'after' => 'head_img'
                    ]
                ),
                new Column(
                    'binds',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "[]",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "绑定账号",
                        'after' => 'signature'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
                new Index('phone', ['phone'], ''),
                new Index('email', ['email'], ''),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '2',
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
        $this->batchInsert('tao_system_user', [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'status',
            'role_ids',
            'seed',
            'password',
            'email',
            'email_at',
            'email_valid',
            'phone',
            'phone_at',
            'phone_valid',
            'nickname',
            'head_img',
            'signature',
            'binds',
        ]);
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
        $this->batchDelete('tao_system_user');
    }
}
