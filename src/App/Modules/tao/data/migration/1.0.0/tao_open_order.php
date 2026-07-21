<?php

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class TaoOpenOrderMigration_100
 */
class TaoOpenOrderMigration_100 extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     * @throws Exception
     */
    public function morph(): void
    {
        $this->morphTable('tao_open_order', [
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
                    'app',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "来源应用",
                        'after' => 'updated_at'
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
                        'comment' => "用户",
                        'after' => 'app'
                    ]
                ),
                new Column(
                    'channel',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "渠道",
                        'after' => 'user_id'
                    ]
                ),
                new Column(
                    'trade_type',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "场景",
                        'after' => 'channel'
                    ]
                ),
                new Column(
                    'rndcode',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 5,
                        'comment' => "随机字符串",
                        'after' => 'trade_type'
                    ]
                ),
                new Column(
                    'appid',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "公众号 ID",
                        'after' => 'rndcode'
                    ]
                ),
                new Column(
                    'mchid',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "商户号 ID",
                        'after' => 'appid'
                    ]
                ),
                new Column(
                    'openid',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "用户标识",
                        'after' => 'mchid'
                    ]
                ),
                new Column(
                    'amount',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "金额/分",
                        'after' => 'openid'
                    ]
                ),
                new Column(
                    'currency',
                    [
                        'type' => Column::TYPE_TINYINTEGER,
                        'default' => "1",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "货币单位",
                        'after' => 'amount'
                    ]
                ),
                new Column(
                    'metadata',
                    [
                        'type' => Column::TYPE_JSON,
                        'notNull' => false,
                        'size' => 1,
                        'comment' => "下单数据",
                        'after' => 'currency'
                    ]
                ),
                new Column(
                    'response',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'notNull' => false,
                        'size' => 1000,
                        'comment' => "订单创建响应数据",
                        'after' => 'metadata'
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
                        'comment' => "订单状态",
                        'after' => 'response'
                    ]
                ),
                new Column(
                    'message',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 255,
                        'comment' => "提示信息",
                        'after' => 'status'
                    ]
                ),
                new Column(
                    'transaction_id',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 50,
                        'comment' => "交易单号",
                        'after' => 'message'
                    ]
                ),
                new Column(
                    'success_time',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "交易时间",
                        'after' => 'transaction_id'
                    ]
                ),
                new Column(
                    'refund_at',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "申请退款时间",
                        'after' => 'success_time'
                    ]
                ),
                new Column(
                    'refund_amt',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "申请退款金额",
                        'after' => 'refund_at'
                    ]
                ),
                new Column(
                    'refund_status',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "退款状态",
                        'after' => 'refund_amt'
                    ]
                ),
                new Column(
                    'refund_id',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'default' => "",
                        'notNull' => true,
                        'size' => 40,
                        'comment' => "退款单号",
                        'after' => 'refund_status'
                    ]
                ),
                new Column(
                    'refund_amount',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "实际退款金额",
                        'after' => 'refund_id'
                    ]
                ),
                new Column(
                    'refund_time',
                    [
                        'type' => Column::TYPE_INTEGER,
                        'default' => "0",
                        'unsigned' => true,
                        'notNull' => true,
                        'size' => 1,
                        'comment' => "实际退款时间",
                        'after' => 'refund_amount'
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
