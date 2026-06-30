<?php

use Phalcon\Db\Column;
use Phalcon\Db\Index;

return [
    'table' => 'tao_system_user_bind',
    'columns' => [
        new Column('id', [
            'type' => Column::TYPE_BIGINTEGER,
            'unsigned' => true,
            'notNull' => true,
            'autoIncrement' => true,
            'size' => 1,
            'first' => true
        ]),
        new Column('user_id', [
            'type' => Column::TYPE_BIGINTEGER,
            'unsigned' => true,
            'default' => "0",
            'notNull' => true,
            'size' => 1,
            'after' => 'id'
        ]),
        new Column('platform', [
            'type' => Column::TYPE_VARCHAR,
            'default' => "",
            'notNull' => true,
            'size' => 30,
            'after' => 'user_id'
        ]),
        new Column('open_id', [
            'type' => Column::TYPE_VARCHAR,
            'default' => "",
            'notNull' => true,
            'size' => 100,
            'after' => 'platform'
        ]),
        new Column('union_id', [
            'type' => Column::TYPE_VARCHAR,
            'default' => "",
            'notNull' => true,
            'size' => 100,
            'after' => 'open_id'
        ]),
        new Column('nickname', [
            'type' => Column::TYPE_VARCHAR,
            'default' => "",
            'notNull' => true,
            'size' => 100,
            'after' => 'union_id'
        ]),
        new Column('avatar', [
            'type' => Column::TYPE_VARCHAR,
            'default' => "",
            'notNull' => true,
            'size' => 255,
            'after' => 'nickname'
        ]),
        new Column('raw_data', [
            'type' => Column::TYPE_TEXT,
            'notNull' => false,
            'after' => 'avatar'
        ]),
        new Column('created_at', [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'notNull' => true,
            'size' => 1,
            'after' => 'raw_data'
        ]),
        new Column('updated_at', [
            'type' => Column::TYPE_INTEGER,
            'default' => "0",
            'notNull' => true,
            'size' => 1,
            'after' => 'created_at'
        ]),
        new Column('deleted_at', [
            'type' => Column::TYPE_INTEGER,
            'notNull' => false,
            'size' => 1,
            'after' => 'updated_at'
        ]),
    ],
    'indexs' => [
        new Index('user_id', ['user_id'], ''),
        new Index('platform_open_id', ['platform', 'open_id'], ''),
    ],
    'options' => [
        'TABLE_COLLATION' => 'utf8mb4_general_ci',
        'ENGINE' => 'InnoDB',
    ],
];
