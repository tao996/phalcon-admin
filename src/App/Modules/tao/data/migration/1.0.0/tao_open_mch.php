<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoOpenMchMigration_100
 */
class TaoOpenMchMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_open_mch', [
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
                    'created_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'after' => 'id'
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
                        'after' => 'created_at'
                    ]
                ),
                new Column(
                    'done',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "资料是否完整",
                        'after' => 'updated_at'
                    ]
                ),
                new Column(
                    'mchid',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "商户 ID",
                        'after' => 'done'
                    ]
                ),
                new Column(
                    'private_key',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "clientKey 路径",
                        'after' => 'mchid'
                    ]
                ),
                new Column(
                    'certificate',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "clientCert 路径",
                        'after' => 'private_key'
                    ]
                ),
                new Column(
                    'secret_key',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "v3 api 秘钥",
                        'after' => 'certificate'
                    ]
                ),
                new Column(
                    'v2_secret_key',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "v2 api 秘钥",
                        'after' => 'secret_key'
                    ]
                ),
                new Column(
                    'platform_cert',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "平台证书",
                        'after' => 'v2_secret_key'
                    ]
                ),
                new Column(
                    'remark',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "备注",
                        'after' => 'platform_cert'
                    ]
                ),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
            ],
            'options' => [
                'TABLE_TYPE' => 'BASE TABLE',
                'AUTO_INCREMENT' => '1',
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
        $this->batchInsert('tao_open_mch', [
            'id',
            'created_at',
            'updated_at',
            'done',
            'mchid',
            'private_key',
            'certificate',
            'secret_key',
            'v2_secret_key',
            'platform_cert',
            'remark',
        ]);
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
        $this->batchDelete('tao_open_mch');
    }
}
