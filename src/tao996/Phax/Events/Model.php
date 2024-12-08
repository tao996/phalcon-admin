<?php

namespace Phax\Events;


use Phalcon\Mvc\Model\Behavior\SoftDelete;
use Phalcon\Mvc\Model\Behavior\Timestampable;

class Model
{
    /**
     * 模型事件
     * @link https://docs.phalcon.io/5.0/en/db-models-events
     * @param \Phalcon\Mvc\Model $model
     * @param string $event 事件名称
     * @param string $filedName 字段名
     * @param string|bool $format 格式
     * @return void
     */
    public static function timestampable(
        \Phalcon\Mvc\Model $model,
        string             $event,
        string             $filedName,
        string|bool        $format
    )
    {
        if (empty($filedName)) {
            return;
        }
        $model->addBehavior(
            new Timestampable(
                [
                    $event => [
                        'field' => $filedName,
                        'format' => self::timestampFormat($format)
                    ]
                ]
            )
        );
    }

    private static function timestampFormat($format)
    {
        if ($format === 'timestamp') {
            return 'Y-m-d H:i:sP';
        } else if ($format === 'int') {
            return time();
        } else {
            return 'Y-m-d H:i:s';
        }
    }

    /**
     * @param \Phalcon\Mvc\Model $model
     * @param string|bool $format
     * @param string $filedName
     * @return void
     */
    public static function softDelete(\Phalcon\Mvc\Model $model,
                                      string|bool        $format,
                                      string             $filedName = 'deleted_at'
    )
    {
        $model->addBehavior(
            new SoftDelete([
                'field' => $filedName,
                'value' => self::printTimestampFormat($format)
            ])
        );
    }

    public static function printTimestampFormat($format)
    {
        if ($format === 'timestamp') {
            return date('Y-m-d H:i:sP');
        } else if ($format === 'int') {
            return time();
        } else {
            return date('Y-m-d H:i:s');
        }
    }
}