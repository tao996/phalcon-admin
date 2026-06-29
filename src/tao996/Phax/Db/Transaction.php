<?php

namespace Phax\Db;


use Phax\Foundation\Application;

class Transaction
{

    /**
     * 事务执行 （Phalcon Model Db 会执行触发事件）
     * @link https://docs.phalcon.io/3.4/en/db-models-transactions 模型事务
     * @param \Phalcon\Db\Adapter\Pdo\AbstractPdo|null $db 数据库连接，如果不提供，则自动从 di 中获取
     * @param callable (\Phalcon\Db\Adapter\Pdo\AbstractPdo):void $handle 处理函数，接收参数
     * @return void
     * @throws \Exception
     */
    public static function db( callable $handle,\Phalcon\Db\Adapter\Pdo\AbstractPdo|null $db = null): void
    {
        if ($db == null){
            $db = Application::di()->get('db');
        }
        $db->begin();
        try {
            $handle($db);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * 使用原生的 PDO 创建事务
     * @param callable $handle
     * @param \PDO|null $pdo 如果不提供，则自动从 di 中获取
     * @return void
     * @throws \Throwable
     */
    public static function pdo( callable $handle,\PDO|null $pdo = null): void
    {
        if ($pdo == null){
            $pdo = Application::di()->get('pdo');
        }
        $pdo->beginTransaction();
        try {
// https://www.php.net/manual/zh/pdo.transactions.php#110483
// MySQL, Oracle 的 DDL 语句会自动触发事务
            $handle($pdo);
            $pdo->commit();
        } catch (\Throwable $e) {
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
        $keys = [];
        $safeValues = [];
        // 遍历两次以保持 key/value 对齐
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                // 兼容 :id 和 id 两种写法
                $keys[] = '/:' . ltrim($key, ':') . '/';
            } else {
                $keys[] = '/[?]/';
            }
        }
        foreach ($params as $value) {
            // 防止替换值中的 $0、\1 等被 preg_replace 当作反向引用处理
            $safeValues[] = str_replace(['\\', '$'], ['\\\\', '\\$'], (string)$value);
        }

        return preg_replace($keys, $safeValues, $query, 1, $count);
    }
}