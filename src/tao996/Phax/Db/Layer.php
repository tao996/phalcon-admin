<?php

namespace Phax\Db;

use Phalcon\Db\Adapter\AdapterInterface;

/**
 * @link https://docs.phalcon.io/latest/db-layer
 */
readonly class Layer
{
    private AdapterInterface $connection;
    private string $table;
    private \Phalcon\Di\DiInterface $di;

    public function __construct(public \Phax\Mvc\Model|null $model)
    {
        if (empty($model)) {
            throw new \Exception('model is empty in QueryBuilder.with');
        }
        $this->connection = $model->getWriteConnection();
        $this->table = $model->getSource();
        $this->di = $this->model->getDI();
    }

    /**
     * 允许 SQL 语句中使用（双下划线） __ 来代替表名 (此替换可以存在风险，使用时需要注意)
     * @param string $sql
     * @return string
     */
    private function replaceTableName(string $sql): string
    {
        return preg_replace('/__/', $this->table, $sql, 1);
    }

    public static function with(string|\Phax\Mvc\Model|null $model): Layer
    {
        if (is_string($model)) {
            $model = call_user_func([$model, 'getObject']);
        }
        return new Layer($model);
    }

    public function insert(array $kv, array|null $valueTypes = null): bool
    {
        return $this->connection->insert(
            $this->table,
            array_values($kv),
            array_keys($kv),
            $valueTypes
        );
    }

    /**
     * 更新记录
     * <code>
     * User::layer()->update(['name'=>'good'], ['id'=>1]);
     * </code>
     * @param array $updateKv 待更新的值
     * @param array $conditionKv 待更新的条件
     * @param array|null $updateValueTypes
     * @param array|null $conditionValueTypes
     * @return bool
     */
    public function update(
        array $updateKv,
        array $conditionKv,
        array|null $updateValueTypes = null,
        array|null $conditionValueTypes = null
    ): bool {
        $conditions = [
            'conditions' => $this->mergeKey($conditionKv),
            'bind' => array_values($conditionKv),
        ];
        if (!empty($conditionValueTypes)) {
            $conditions['bindTypes'] = $conditionValueTypes;
        }
        return $this->connection->update(
            $this->table,
            array_keys($updateKv),
            array_values($updateKv),
            $conditions,
            $updateValueTypes
        );
    }


    public function incr(string $column, array $conditions, int $num = 1): bool
    {
        $data = (new Parameter(false))
            ->where($conditions)
            ->update($column . '=' . $column . '+' . $num, $this->table);

        return $this->connection->execute($data['sql'], $data['bind'], $data['bindTypes']);
    }

    public function desc(string $column, array $conditions, int $num = 1): bool
    {
        $data = (new Parameter(false))
            ->where($conditions)
            ->update($column . '=' . $column . '-' . $num, $this->model->getSource());

        return $this->connection->execute($data['sql'], $data['bind'], $data['bindTypes']);
    }

    /**
     * 执行删除操作
     * <code>
     * Order::layer()->delete(['id'=>4]);
     * </code>
     * @param array $kv 删除的条件 [id=>4]
     * @param array $valueTypes 值所绑定的类型
     * @return bool
     * @throws \Exception
     */
    public function delete(array $kv, array $valueTypes = []): bool
    {
        if (empty($kv)) {
            throw new \Exception('delete conditions should not empty');
        }
        return $this->connection->delete($this->table, $this->mergeKey($kv), array_values($kv), $valueTypes);
    }

    private function mergeKey(array $kv): string
    {
        $keys = [];
        foreach (array_keys($kv) as $key) {
            $keys[] = $key . '=?';
        }
        return join(' AND ', $keys);
    }

    /**
     * 执行 SQL 语句
     * <code>
     * $sql = 'UPDATE __ SET `order_num`=`order_num`-1 WHERE `id`=1';
     * Order::layer()->execute($sql);
     * </code>
     * @param string $sql
     * @param true $replaceTableName
     * @return bool
     */
    public function execute(string $sql, true $replaceTableName = true): bool
    {
        $sql = $replaceTableName ? $this->replaceTableName($sql) : $sql;
        return $this->connection->execute($sql);
    }

    /**
     * 使用 PDO 批量添加记录（不会触发模型事件，即 created_at/updated_at 不会自动填充）
     * @link https://www.php.net/manual/zh/pdo.commit.php Demo 1
     * @param array $rows 待添加的数据
     * @param array|string $fields 添加的列，如果为空，则从 end($rows) 中获取
     * @throws \Exception
     */
    public function batchInsert(array $rows, string|array $fields = [], bool $transaction = true): \PDO|null
    {
        if (empty($rows)) {
            return null;
        }

        if (empty($fields)) {
            $fields = array_keys(end($rows));
        } elseif (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        /**
         * @var \PDO $pdo
         */
        $pdo = $this->di->get('pdo');
        $sql = 'INSERT INTO ' . $this->table . ' ( ' . (is_array($fields) ? join(
                ',',
                $fields
            ) : $fields) . ' ) VALUES ( ' . rtrim(str_repeat('?,', count(end($rows))), ',') . ' )';
        if ($transaction) {
            Transaction::pdo($pdo, function () use ($sql, $rows, $pdo) {
                $this->insertWith($sql, $rows, $pdo);
            });
        } else {
            $this->insertWith($sql, $rows, $pdo);
        }
        return $pdo;
    }

    private function insertWith(string $sql, array $rows, \Pdo $pdo): void
    {
        $stmt = $pdo->prepare($sql);
        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
    }

}