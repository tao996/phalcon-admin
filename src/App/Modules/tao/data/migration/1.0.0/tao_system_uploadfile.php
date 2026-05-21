<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoSystemUploadfileMigration_100
 */
class TaoSystemUploadfileMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_system_uploadfile', [
            'columns' => [
                new Column(
                    'id',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'unsigned' => true,
                        'notNull' => true,
                        'autoIncrement' => true,
                        'size' => 1,
                        'comment' => "ID",
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
                        'after' => 'id'
                    ]
                ),
                new Column(
                    'upload_type',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "local",
                        'notNull' => true,
                        'size' => 20,
                        'comment' => "存储位置",
                        'after' => 'user_id'
                    ]
                ),
                new Column(
                    'summary',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "文件原名",
                        'after' => 'upload_type'
                    ]
                ),
                new Column(
                    'url',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "路径",
                        'after' => 'summary'
                    ]
                ),
                new Column(
                    'width',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "宽度",
                        'after' => 'url'
                    ]
                ),
                new Column(
                    'height',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "高度",
                        'after' => 'width'
                    ]
                ),
                new Column(
                    'frames',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "图片帧数",
                        'after' => 'height'
                    ]
                ),
                new Column(
                    'mime_type',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'comment' => "mime类型",
                        'after' => 'frames'
                    ]
                ),
                new Column(
                    'file_size',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "文件大小",
                        'after' => 'mime_type'
                    ]
                ),
                new Column(
                    'file_ext',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 100,
                        'after' => 'file_size'
                    ]
                ),
                new Column(
                    'sha1',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 40,
                        'comment' => "文件 sha1编码",
                        'after' => 'file_ext'
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
                        'comment' => "创建日期",
                        'after' => 'sha1'
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
        $this->batchInsert('tao_system_uploadfile', [
            'id',
            'user_id',
            'upload_type',
            'summary',
            'url',
            'width',
            'height',
            'frames',
            'mime_type',
            'file_size',
            'file_ext',
            'sha1',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down(): void
    {
        $this->batchDelete('tao_system_uploadfile');
    }
}
