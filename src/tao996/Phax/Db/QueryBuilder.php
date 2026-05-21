<?php

namespace Phax\Db;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model;
use Phax\Foundation\Application;
use Phax\Utils\MyData;

/**
 * 请求参数处理 Phalcon 写法
 * @link https://docs.phalcon.io/5.0/en/db-phql#parameters-1
 */
class QueryBuilder
{

    private \Phax\Mvc\Model $model;
    private Parameter $parameter;

    public function __construct(\Phax\Mvc\Model $model = null)
    {
        $this->parameter = new Parameter();
        if (!is_null($model)) {
            $this->model = $model;
            $this->parameter->parameter['models'] = get_class($model);
        }
    }

    public function getModel(): \Phax\Mvc\Model
    {
        return $this->model;
    }


    public static function with(string|\Phax\Mvc\Model|null $model): QueryBuilder
    {
        if (empty($model)) {
            throw new \Exception('model is empty in QueryBuilder.with');
        }
        if (is_string($model)) {
            $model = call_user_func([$model, 'getObject']);
        }
        $qb = new QueryBuilder($model);
        $qb->softDelete();
        return $qb;
    }

    /**
     * 查询全部记录（包含软删除）
     * @return $this
     */
    public function withTrashed(): static
    {
        $this->parameter->withTrashed();
        return $this;
    }

    /**
     * 注意：如果使用 columns，那么查询记录时返回的是 Phalcon\Mvc\Model\Row (即不完整的记录，不能用于 save())
     * @param $columns array|string 查询的字段
     * @return $this
     */
    public function columns(array|string $columns): static
    {
        $this->parameter->columns($columns);
        return $this;
    }

    /**
     * columns 的同名方法
     * @param $fields array|string 查询的字段
     * @return $this
     * @throws \Exception
     */
    public function field(array|string $fields): static
    {
        $this->parameter->columns($fields);
        return $this;
    }

    /**
     * like 查询
     * @param string $name 字段名
     * @param mixed $v 值，不需要填写 %% 号
     * @return $this
     * @throws \Exception
     */
    public function like(string $name, mixed $v): static
    {
        $this->parameter->like($name, $v);
        return $this;
    }

    /**
     * <code>
     * $qbLikes = new \Phax\Db\Parameter();
     * $qbLikes->likes('name', ['aaa', 'bbb']);
     * $this->assertEquals([
     *      "bind" => [ "name__0" => "%aaa%", "name__1" => "%bbb%" ],
     *      "bindTypes" => [ "name__0" => 2, "name__1" => 2, ],
     *      "conditions" => "name LIKE :name__0: OR name LIKE :name__1:"
     * ], $qbLikes->getParameter());
     * </code>
     * @param string $name 字段名称
     * @param array $vs 字段可选的值集合
     * @return $this
     */
    public function likes(string $name, array $vs): static
    {
        $this->parameter->likes($name, $vs);
        return $this;
    }

    /**
     * <code>
     * $qbOrLike = new \Phax\Db\Parameter();
     * $qbOrLike->orLike(['title', 'keyword'], 'hello');
     * $this->assertEquals([
     *      "bind" => [ "title" => "%hello%", "keyword" => "%hello%" ],
     *      "bindTypes" => [ "title" => 2, "keyword" => 2, ],
     *      "conditions" => "title LIKE :title:  OR keyword LIKE :keyword: "
     * ], $qbOrLike->getParameter());
     * </code>
     * @param array $names 字段集合
     * @param mixed $v 值
     * @return $this
     * @throws \Exception
     */
    public function orLike(array $names, mixed $v): static
    {
        $this->parameter->orLike($names, $v);
        return $this;
    }

    public function between(string $name, mixed $min, mixed $max, int $type = \PDO::PARAM_INT): static
    {
        $this->parameter->between($name, $min, $max, $type);
        return $this;
    }

    /**
     * 不查询列值
     * @param array $columns 不要查询的字段
     * @return $this
     */
    public function excludeColumns(array $columns = []): static
    {
        $row = $this->model->getModelsMetaData()->getAttributes($this->model);
        $this->columns(array_diff($row, $columns));
        return $this;
    }

    /**
     * 启用软删除
     * @return $this
     */
    public function softDelete(): static
    {
        $this->parameter->sortDelete($this->model);
        return $this;
    }

    /**
     * 设置条件,支持多种格式查询 <pre>
     * 1. 直接 sql 语句，如 where('id=5'), where(['id'=>5, 'age'=>6]), where(['id=5','age=6'])
     * 2. name,value 格式，如 where('id',5), where('id',[1,2,3])
     * 3. name,opt,value 格式，如 where('id','=',5)
     * </pre>
     * @throws \Exception
     */
    public function where(...$params): static
    {
        $this->parameter->where(...$params);
        return $this;
    }


    /**
     * 操作
     * @param string $name 字段名称
     * @param string $opt 操作符 like, =, >= ...
     * @param mixed $value 字段值
     * @param int $bindType 绑定类型，默认 -1 表示从模型中获取，其它使用 \PDO::PARAM_XXX
     * @return static
     * @throws \Exception
     */
    public function opt(string $name, string $opt, mixed $value, int $bindType = -1): static
    {
        $this->parameter->opt($name, $opt, $value, $this->model, $bindType);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function string(string $name, $value, $allowEmpty = false): static
    {
        if ($allowEmpty || !empty($value)) {
            $this->opt($name, '=', $value, \PDO::PARAM_STR);
        }
        return $this;
    }

    /**
     * 绑定一个整数
     * @param string $name 字段名称
     * @param mixed $value 待检查的值，会被 intval 处理
     * @param bool $skipEmpty 如果为空值，则跳过
     * @return $this
     */
    public function int(string $name, string|int|null $value, bool $skipEmpty = true): static
    {
        $this->parameter->int($name, $value, $skipEmpty);
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function in(string $name, array $values): static
    {
        $this->parameter->in($name, $values);
        return $this;
    }

    /**
     * 添加一个简单的条件操作
     * @param string $condition 简单条件，示例：id=5
     * @param bool $compare 只有条件为 true 时，才会启用
     * @return $this
     */
    public function and(string $condition, bool $compare): static
    {
        $this->parameter->and($condition, $compare);
        return $this;
    }

    /**
     * @param string $name
     * @param string|int $value
     * @param bool $allowEmpty
     * @return $this
     * @throws \Exception
     */
    public function notEqual(string $name, string|int $value, bool $allowEmpty = false): static
    {
        $this->parameter->notEqual($name, $value, $allowEmpty);
        return $this;
    }

    /**
     * 分组并整理结果
     * @param string $field 待分组的字段，只能指定一个字段
     * @return array `[$fieldValue => $total]`
     */
    public function group(string $field): array
    {
        $this->parameter->group($field);
        if ($rows = $this->field(['count(*) as total', $field])->find()) {
            return array_column($rows, 'total', $field);
        }
        return [];
    }

    /**
     * 计算 sum
     * @param string $filed
     * @return int|mixed
     * @throws \Exception
     */
    public function sum(string $filed)
    {
        $row = $this->field("sum({$filed}) as s")->findFirstArray();
        return $row['s'] ?? 0;
    }

    /**
     * 过滤
     * @param string $filter 过滤条件 "status=1"
     * @return $this
     */
    public function having(string $filter): static
    {
        $this->parameter->having($filter);
        return $this;
    }

    /**
     * 排序，支持多种写法 <pre>
     * 'id' 等价于 'id asc'
     * 'a_id, b_id' 等价于 'a_id asc, b_id asc'
     * </pre>
     * @param string|array $order 排序条件
     * @return self
     */
    public function orderBy(array|string $order): static
    {
        $this->parameter->orderBy($order);
        return $this;
    }

    /**
     * 分页
     * @param int $page 第几页，首页为0
     * @param int $limit
     * @return self
     */
    public function pagination(int $page, int $limit = 15): static
    {
        $this->parameter->pagination($page, $limit);
        return $this;
    }

    /**
     * 取消分页
     * @return $this
     */
    public function disabledPagination(): static
    {
        $this->parameter->disabledPagination();
        return $this;
    }

    /**
     * 限制每次查询记录数量
     * @param int|null $limit 记录数，至少为 1
     * @param int $max 允许最多的查询数据量
     * @return $this
     */
    public function limit(int|null $limit, int $max = 15): static
    {
        $this->parameter->limit($limit, $max);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->parameter->offset($offset);
        return $this;
    }

    /**
     * 使用 distinct 时会影响 find 的结果，自动提取列值
     * @param string $name
     * @return $this
     * @throws \Exception
     */
    public function distinct(string $name): static
    {
        $this->parameter->distinct($name);
        return $this;
    }

    public function joins(array $joins): static
    {
        $this->parameter->joins($joins);
        return $this;
    }

    public function getParameter(): array
    {
        return $this->parameter->getParameter();
    }

    public function builder(): \Phalcon\Mvc\Model\Query\Builder
    {
        // 在 workerman 下，长久没有连接会导致  General error: 2006 MySQL server has gone away
        // 必须设置 onWorkerStart 设置定时连接
        if (empty($this->parameter->parameter['container'])) {
            $this->parameter->parameter['container'] = Application::di();
        }
        return new \Phalcon\Mvc\Model\Query\Builder($this->getParameter());
    }

    /**
     * todo prepare
     * @param DiInterface|null $di
     * @return $this
     */
    public function setContainer(DiInterface $di = null): static
    {
//        if (!is_null($di)) {
//            $this->parameter->parameter['container'] = $di;
//        }
        return $this;
    }

    public function count(): int
    {
        $params = $this->getParameter();
        return $this->model::count($params);
    }

    /**
     * @throws \Exception
     */
    public function exits(): bool
    {
        $this->columns('id');
        return !empty($this->findFirstArray());
    }

    /**
     * @throws \Exception
     */
    public function notExists(): bool
    {
        return !$this->exits();
    }

    private array $joinInfo = [];

    /**
     * TODO 联表查询；只会影响到 find/findFirst
     * @param string $referenceModel Profile::class 联表类名
     * @param array|string $fields 查询的字段
     * @param string $foreignKey 外键
     * @param string $referenceModelKey 联表类，默认为 id
     * @return $this
     */
    public function join(
        string $referenceModel,
        mixed $fields,
        string $foreignKey,
        string $referenceModelKey = 'id'
    ): static {
        $parts = explode('\\', $referenceModel);
        $this->joinInfo[] = [
            $referenceModel,
            $fields,
            $foreignKey,
            $referenceModelKey,
            lcfirst(end($parts)),// 4
        ];
//        dd($this->joinInfo);
        return $this;
    }

    /**
     * @param array $rst
     * @param bool $deepArray 是否多维数组
     * @return void
     */
    private function doJoinWithResult(array &$rst, bool $deepArray = false): void
    {
        if ($deepArray) {
            if ($this->joinInfo) {
                foreach ($this->joinInfo as $joinInfo) {
                    $ids = [];
                    foreach ($rst as $item) {
                        $ids[] = $item[$joinInfo[2]];
                    }
                    $rows = QueryBuilder::with($joinInfo[0])
                        ->in($joinInfo[3], $ids)->findColumn(
                            [$joinInfo[3], $joinInfo[1]],
                            $joinInfo[3]
                        );

                    foreach ($rst as $index => $item) {
                        $key = $item[$joinInfo[2]];
                        $rst[$index][$joinInfo[4]] = $rows[$key] ?? [];
                    }
                }
            }
        } else {
            foreach ($this->joinInfo as $joinInfo) {
                if (isset($rst[$joinInfo[2]])) {
                    $rst[$joinInfo[4]] = QueryBuilder::with($joinInfo[0])
                        ->int($joinInfo[3], $rst[$joinInfo[2]])
                        ->columns([$joinInfo[3], $joinInfo[1]])
                        ->findFirstArray();
                }
            }
        }
    }

    /**
     * 查寻符合条件的所有记录
     * @return array
     */
    public function find(): array
    {
        $rows = $this->builder()->getQuery()->execute()?->toArray();
        if (is_null($rows)) {
            return [];
        }
        if (!empty($this->parameter->parameter['distinct'])) {
            return array_column($rows, $this->parameter->parameter['distinct']);
        }
        $this->doJoinWithResult($rows, true);

        return $rows;
    }

    /**
     * 查询符合条件的首行记录，默认返回数组
     * @param callable|null $callback 回调函数
     * @param bool $toArray 如果为 false 则返回模型 不会联表查询
     * @return array|Model|null|mixed|\Phalcon\Mvc\Model\Row 注意返回的不是具体模型，可能需要再次转换
     */
    protected function findFirst(callable $callback = null, bool $toArray = true)
    {
        $this->parameter->parameter['limit'] = 1;
        $this->parameter->parameter['offset'] = 0;
        $record = $this->builder()->getQuery()->execute()?->getFirst();
        if (is_null($record)) {
            return $toArray ? [] : null;
        }
        $row = $toArray ? $record->toArray() : $record;
        if ($toArray) { // todo 联表查询
            $this->doJoinWithResult($row, false);
        }
        if ($callback && $row) {
            $callback($row);
        }
        return $row;
    }

    /**
     * 查询符合条件的记录模型
     * @param callable|null $callback 回调函数
     * @return Model|null
     */
    public function findFirstModel(callable $callback = null): mixed
    {
        return $this->findFirst($callback, false);
    }

    public function findFirstArray(callable $callback = null): array
    {
        return $this->findFirst($callback, true);
    }

    /**
     * 查询符合条件的所有记录
     * @param array|string $fields 指定要查询的字段
     * @param string|null $key 如果设置，则会将此字段的值提升为查询记录的 key
     * @throws \Exception
     */
    public function findColumn(array|string $fields, string $key = null): array
    {
        $this->columns($fields);
        $rows = $this->find();
        return $key ? MyData::columnMap($rows, $key) : $rows;
    }

    /**
     * 获取第1条记录指定列的值
     * @param string $column
     * @return mixed
     */
    public function value(string $column): mixed
    {
        if ($row = $this->columns($column)->findFirstArray()) {
            return $row[$column];
        }
        return null;
    }

    /**
     * 删除符合条件的记录 (注意：使用软删除会触发 beforeSave)
     * @return bool
     */
    public function delete(): bool
    {
        return $this->model::find($this->getParameter())->delete();
    }

    /**
     * 更新记录(会触发事件)，如果不想被触发，使用 DbLayer 方法
     * @param array $array
     * @return bool
     */
    public function update(array $array): bool
    {
        $data = $this->parameter->update($array, get_class($this->model));

        $result = $this->model->getModelsManager()->executeQuery($data['sql'], $data['bind'], $data['bindTypes']);
        return $result->success();
    }
}