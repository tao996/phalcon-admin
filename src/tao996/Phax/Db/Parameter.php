<?php

namespace Phax\Db;

use Exception;
use Phalcon\Di\Di;
use Phax\Mvc\Model;

class Parameter
{

    /**
     * @var array{container:Di,columns:array,bind:array,bindTypes:array,conditions:array,distinct:string,group:array,having:string,joins:array,limit:array,offset:int,models:array,order:array}
     */
    public array $parameter = [
//        'container' => null,
//        'columns' => [],
        'bind' => [],
        'bindTypes' => [],
        'conditions' => '',
//        'distinct' => '', // distinct column
//        'group' => [],
//        'having' => '', // having columns
//        'joins' => [],
//        'limit' => [],
//        'offset' => 15,
//        'models' => [],
//        'order' => [],
    ];
    private int $numberLen = 0;

    /**
     * @param bool $numPlaceholder 占位符格式，如果为 true 则使用 ?0,?1；如果为 false 则使用 ?, ?
     */
    public function __construct(public bool $numPlaceholder = true)
    {
    }

    /**
     * @throws Exception
     */
    public function columns(array|string $columns): static
    {
        if (empty($columns)) {
            return $this;
        }
        if (func_num_args() > 1) {
            throw new Exception('parameter columns should be "id,age" or ["id","age"]');
        }
        $this->parameter['columns'] = is_array($columns) ? join(',', $columns) : $columns;
        return $this;
    }

    public function sortDelete(Model $model): static
    {
        if ($model->isSoftDelete()) {
            $this->appendConditionSQL($model->getDeleteTimeName() . ' IS NULL');
        }
        return $this;
    }

    private function appendConditionSQL(string $sql): void
    {
        if (empty($sql)) {
            return;
        }
        if (empty($this->parameter['conditions'])) {
            $this->parameter['conditions'] = $sql;
        } else {
            $this->parameter['conditions'] .= (' AND (' . $sql . ')');
        }
    }

    /**
     * 一次性设置查询条件
     * @param string $condition 'created > :min: AND created < :max:'
     * @param array $bindValues ['min' => '2013-01-01', 'max' => '2013-10-10']
     * @param array $bindTypes ['min' => \PDO::PARAM_STR, 'max' => \PDO::PARAM_STR]
     * @return Parameter
     */
    public function bindConditions(string $condition, array $bindValues = [], array $bindTypes = []): static
    {
        $this->appendConditionSQL($condition);
        $this->parameter['bind'] = $bindValues;
        $this->parameter['bindTypes'] = $bindTypes;
        return $this;
    }

    /**
     * 获取条件中的绑定参数
     * @param $condition string 如条件为 age=:xxx:
     * @return array 绑定参数的名称 xxx
     * @throws Exception
     */
    private function takeBindKey(string $condition): array
    {
        preg_match_all('|:(\w+):|', $condition, $match);
        if (empty($match[1])) {
            throw new Exception('could not find the bindValue :name: in queryBuilder.takeBindKey');
        }
        return $match[1];
    }

    /**
     * 条件搜索，需要使用 :: 占位符
     * @param string $condition 条件 age = :min:； 或者直接 age=5
     * @param mixed|null $value 值，如果值为空，则会跳过值绑定
     * @param int $type 绑定类型
     * @throws Exception
     */
    public function placeholderCondition(string $condition, mixed $value = null, int $type = \PDO::PARAM_STR): static
    {
        if (empty($condition)) {
            return $this;
        }
        $this->appendConditionSQL($condition);

        if (!is_null($value)) {
            foreach ($this->takeBindKey($condition) as $name) {
                $this->parameter['bind'][$name] = $value;
                $this->parameter['bindTypes'][$name] = $type;
            }
        }
        return $this;
    }

    /**
     * like 查询
     * @param string $name 字段名
     * @param mixed $v 值，不需要填写 %% 号
     * @return Parameter
     * @throws Exception
     */
    public function like(string $name, mixed $v): static
    {
        if (!empty($v)) {
            $this->placeholderCondition(
                $name . ' LIKE :' . $name . ':',
                '%' . $v . '%'
            );
        }
        return $this;
    }

    public function likes(string $name, array $vs): static
    {
        if (!empty($vs)) {
            $conditions = [];

            foreach ($vs as $index => $v) {
                $nameIndex = $name . '__' . $index;
                $conditions[] = $name . ' LIKE :' . $nameIndex . ':'; //, '%' . $v . '%', ;
                $this->parameter['bind'][$nameIndex] = '%' . $v . '%';
                $this->parameter['bindTypes'][$nameIndex] = \PDO::PARAM_STR;
            }
            $condition = join(' OR ', $conditions);
            $this->appendConditionSQL($condition);
        }
        return $this;
    }

    /**
     * @throws Exception
     */
    public function orLike(array $names, mixed $v): static
    {
        if (!empty($v)) {
            $condition = join(' OR ', array_map(function ($name) {
                return $name . ' LIKE :' . $name . ': ';
            }, $names));
            $this->placeholderCondition($condition, '%' . $v . '%');
        }
        return $this;
    }

    public function between(string $name, mixed $min, mixed $max, int $type = \PDO::PARAM_INT): static
    {
        if (!empty($min)) {
            $this->opt($name, '>=', $min, null, $type);
        }
        if (!empty($max)) {
            $this->opt($name, '<=', $max, null, $type);
        }
        return $this;
    }

    /**
     * 设置条件,支持多种格式查询 <pre>
     * 1. 直接 sql 语句，如 where('id=5'), where(['id'=>5, 'age'=>6]), where(['id=5','age=6'])
     * 2. name,value 格式，如 where('id',5), where('id',[1,2,3])
     * 3. name,opt,value 格式，如 where('id','=',5), where('id', 'in', [1,2,3])
     * </pre>
     * @throws \Exception
     */
    public function where(...$params): static
    {
        $paramsLen = count($params);
        switch ($paramsLen) {
            case 1:
                if (is_array($params[0])) {
                    foreach ($params[0] as $key => $value) {
                        if (is_string($key) && is_scalar($value)) { // ['id'=>5]
                            $this->opt($key, '=', $value, null);
                        } elseif (is_int($key) && is_string($value)) { // [0=>'id=5']
                            $this->and($value, true);
                        } elseif (is_array($value)) {
                            $this->in($key, $value);
                        } else {
                            throw new \Exception('unsupported conditions in where');
                        }
                    }
                } else {
                    $this->and($params[0], true);
                }
                break;
            case 2:
                if (is_array($params[1])) {
                    $this->in($params[0], $params[1]);
                    break;
                }
                $this->opt($params[0], '=', $params[1], null);
                break;
            case 3:
                $this->opt($params[0], $params[1], $params[2], null);
                break;
            default:
                throw new \Exception('unsupported params in where');
        }
        return $this;
    }

    /**
     * 操作
     * @param string $name 字段名称
     * @param string $opt 操作符 like, =, >= ...
     * @param mixed $value 字段值
     * @param Model|null $model
     * @param int $bindType 绑定类型，默认 -1 表示从模型中获取，其它使用 \PDO::PARAM_XXX
     * @return Parameter
     * @throws Exception
     */
    public function opt(string $name, string $opt, mixed $value, Model|null $model = null, int $bindType = -1): static
    {
        $opt = strtolower($opt);
        if ($opt == 'in') {
            $this->in($name, $value);
            return $this;
        }
        $this->appendConditionSQL(
            $this->numPlaceholder
                ? "{$name} {$opt} ?{$this->numberLen}"
                : "{$name} {$opt} ?"
        );
        switch (strtolower($opt)) {
            case 'like':
                $value = '%' . $value . '%';
                $bindType = \PDO::PARAM_STR;
                break;
        }

        if ($bindType == -1) {
            if (!empty($model)) {
                $bindType = $model->getDataTypeBinds($name);
            } elseif (is_string($value)) { // 需要放在前面，否则手机号之类的就直接被作为 int 处理掉
                $bindType = \PDO::PARAM_STR;
            } elseif (is_bool($value)) {
                $bindType = \PDO::PARAM_BOOL;
            } elseif (is_numeric($value)) { // 数字/数字字符串
                // https://www.php.net/manual/zh/function.is-numeric.php
                $bindType = \PDO::PARAM_INT;
            } else {
                $bindType = \PDO::PARAM_STR;
            }
        }

        if ($this->numPlaceholder) {
            $this->parameter['bind'][$this->numberLen] = $value;
            $this->parameter['bindTypes'][$this->numberLen] = $bindType;
            $this->numberLen++;
        } else {
            $this->parameter['bind'][] = $value;
            $this->parameter['bindTypes'][] = $bindType;
        }
        return $this;
    }

    /**
     * 绑定一个整数
     * @param string $name 字段名称
     * @param mixed $value 待检查的值，会被 intval 处理
     * @param bool $skipEmpty 如果为空值，则跳过
     * @return Parameter
     * @throws Exception
     */
    public function int(string $name, string|int|null $value, bool $skipEmpty = true): static
    {
        $v = intval($value);
        if (empty($v) && $skipEmpty) {
            return $this;
        }

        $this->opt($name, '=', $v, null, \PDO::PARAM_INT);
        return $this;
    }

    public function in(string $name, array $values): static
    {
        if (!empty($values)) {
            if (is_string(end($values))) {
                $this->appendConditionSQL(
                    $name . ' IN (' . join(',', array_map(function ($v) {
                        return '"' . $v . '"';
                    }, $values)) . ')'
                );
            } else {
                $this->appendConditionSQL($name . ' IN (' . join(',', $values) . ')');
            }
        }
        return $this;
    }

    /**
     * 添加一个简单的条件操作
     * @param string $condition 简单条件，示例：id=5
     * @param bool $compare 只有条件为 true 时，才会启用
     * @return Parameter
     */
    public function and(string $condition, bool $compare): static
    {
        if ($compare) {
            $this->appendConditionSQL($condition);
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string|int $value
     * @param bool $allowEmpty
     * @return Parameter
     * @throws Exception
     */
    public function notEqual(string $name, string|int $value, bool $allowEmpty = false): static
    {
        if (empty($value) && !$allowEmpty) {
            return $this;
        }
        $this->opt($name, '!=', $value, null, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        return $this;
    }

    /**
     * 分组
     * @param array|string $fields ['id', 'name']
     * @return Parameter
     */
    public function group(array|string $fields): static
    {
        $this->parameter['group'] = is_string($fields) ? explode(',', $fields) : $fields;
        return $this;
    }

    /**
     * 过滤
     * @param string $filter 过滤条件 "status=1"
     * @return Parameter
     */
    public function having(string $filter): static
    {
        $this->parameter['having'] = $filter;
        return $this;
    }

    /**
     * 排序，支持多种写法 <pre>
     * 'id' 等价于 'id asc'
     * 'a_id, b_id' 等价于 'a_id asc, b_id asc'
     * </pre>
     * @param string|array $order 排序条件
     * @return Parameter
     */
    public function orderBy(array|string $order): static
    {
        $this->parameter['order'] = $order;
        return $this;
    }

    /**
     * 分页
     * @param int $page 第几页，首页为0
     * @param int $limit
     * @return Parameter
     */
    public function pagination(int $page, int $limit = 15): static
    {
        $this->parameter['limit'] = $limit > 0 ? $limit : 15;
        $this->parameter['offset'] = max($page, 0) * $limit;
        return $this;
    }

    /**
     * 取消分页
     * @return Parameter
     */
    public function disabledPagination(): static
    {
        unset($this->parameter['limit'], $this->parameter['offset']);
        return $this;
    }

    /**
     * 限制每次查询记录数量
     * @param int|null $limit 记录数，至少为 1
     * @param int $max 允许最多的查询数据量
     * @return Parameter
     */
    public function limit(int|null $limit, int $max = 15): static
    {
        if (intval($limit) < 1) {
            $limit = $max;
        }
        $this->parameter['limit'] = min($limit, $max);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->parameter['offset'] = $offset;
        return $this;
    }

    /**
     * 使用 distinct 时会影响 find 的结果，自动提取列值
     * @param string $name
     * @return Parameter
     * @throws Exception
     */
    public function distinct(string $name): static
    {
        $this->columns('distinct(' . $name . ') AS ' . $name);
        $this->parameter['distinct'] = $name;
        return $this;
    }

    public function joins(array $joins): void
    {
        $this->parameter['joins'] = $joins;
    }

    /**
     * @return array{container:Di,columns:array,bind:array,bindTypes:array,conditions:array,distinct:string,group:array,having:string,joins:array,limit:array,offset:int,models:array,order:array}
     */
    public function getParameter(): array
    {
        return $this->parameter;
    }

    /**
     * 更新数据
     * https://docs.phalcon.io/latest/db-layer/#update
     * <code>
     * $param = new \Phax\Db\Parameter();
     * $param->where('id=5');
     *
     * $bb = $param->update(['age' => 10, 'name' => 5], 'abc');
     * $this->assertEquals([
     *      'sql' => "UPDATE abc SET age = ?0,name = ?1 WHERE id=5",
     *      'bind' => [10, 5],
     *      'bindTypes' => [1, 1]
     * ], $bb);
     *
     * $bb = $param->update('age=age+1','abc');
     * $this->assertEquals([
     *      'sql' => "UPDATE abc SET age=age+1 WHERE id=5",
     *      'bind' => [],
     *      'bindTypes' => []
     * ], $bb);
     * </code>
     * @param array|string $updates 更新的数据
     * @param string $source 通常为模型名称（不是表名）
     * @return array{sql:string,bind:array,bindTypes:array} 通常用在 model->getModelsManager()->executeQuery(sql, bind, bindTypes) 中
     */
    public function update(array|string $updates, string $source = '__'): array
    {
        $pp = $this->getParameter();
        if (empty($pp['conditions'])) {
            throw new \Exception('upload condition is empty');
        }
        if (is_array($updates)) {
            $sets = [];
            foreach ($updates as $key => $value) {
                if ($this->numPlaceholder) {
                    $sets[] = "{$key} = ?{$this->numberLen}";
                    $this->numberLen++;
                } else {
                    $sets[] = "{$key}=?";
                }
                $pp['bind'][] = $value;
                $pp['bindTypes'][] = is_string($value) ? \PDO::PARAM_STR : \PDO::PARAM_INT;
            }

            $joinSets = join(',', $sets);
        } else {
            $joinSets = $updates;
        }
        $sql = "UPDATE {$source} SET {$joinSets} WHERE {$pp['conditions']}";
        return [
            'sql' => $sql,
            'bind' => $pp['bind'],
            'bindTypes' => $pp['bindTypes']
        ];
    }

}