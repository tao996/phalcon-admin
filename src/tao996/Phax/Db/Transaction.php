<?php

namespace Phax\Db;


class Transaction
{

    /**
     * 事务执行 （Phalcon Model Db 会执行触发事件）
     * @link https://docs.phalcon.io/3.4/en/db-models-transactions 模型事务
     * @param \Phalcon\Db\Adapter\Pdo\AbstractPdo $db 数据库连接
     * @param callable (\Phalcon\Db\Adapter\Pdo\AbstractPdo):void $handle 处理函数，接收参数
     * @return void
     * @throws \Exception
     */
    public static function db(\Phalcon\Db\Adapter\Pdo\AbstractPdo $db, callable $handle): void
    {
        $db->begin();
        try {
            $handle($db);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public static function pdo(\PDO $pdo, callable $handle): void
    {
        $pdo->beginTransaction();
        try {
// https://www.php.net/manual/zh/pdo.transactions.php#110483
// MySQL, Oracle 的 DDL 语句会自动触发事务
            $handle($pdo);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * 打印 PDO 语句
     * @link https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements
     * @param string $query
     * @param array $params
     * @return string
     */
    public static function getRawPdoSql(string $query, array $params = []): string
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }
        }

        return preg_replace($keys, $params, $query, 1, $count);
    }
}