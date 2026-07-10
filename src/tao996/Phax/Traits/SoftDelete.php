<?php

namespace Phax\Traits;

use Phax\Foundation\AppService;

/**
 * 软删除：重写了 findFirst/find/query/findByXxx, findFirstByXxx
 */
trait SoftDelete
{
    public $useSortDelete = true;

    /**
     * 告知 QueryBuilder 该模型启用了软删除
     */
    public function supportSoftDelete(): bool
    {
        return true;
    }

    /**
     * 删除
     * @param $force bool 是否强制删除记录
     * @return bool
     */
    public function destroy(bool $force = false)
    {
        return $force ? parent::delete() : $this->delete();
    }

    /**
     * 恢复软删除的数据
     * @return bool
     */
    public function restore()
    {
        $this->{$this->deletedTime} = null;
        return $this->update();
    }

    /**
     * 软删除
     * @return bool
     */
    public function delete(): bool
    {
        $this->{$this->deletedTime} = \Phax\Events\Model::printTimestampFormat($this->autoWriteTimestamp);
        return $this->update();
    }

    public function isDelete(): bool
    {
        return !is_null($this->{$this->deletedTime});
    }

    /**
     * 单记录 (deleted_at IS NULL)的查询
     * @param $parameters string|numeric|array|null 查询参数 <br>
     * findFirst() // 查询最后一条记录 id DESC <br>
     * findFirst(5) // 数字 查询主键 id=5 的记录（参数化绑定）<br>
     * findFirst('name="phx"') // 字符串，直接设置条件<br>
     * findFirst(['name'=>'phx', 'age'=>5]) // 使用绑定方式
     * @param callable|null $queryBuilder \Phalcon\Mvc\Model\Query\Builder()
     * @throws \Exception
     * @return \Phalcon\Mvc\Model\Row|\Phalcon\Mvc\Model|self|null
     */
    public static function findFirst($parameters = null, callable $queryBuilder = null): mixed
    {
        /**
         * @var $obj \Phax\Mvc\Model
         */
        $obj = static::getObject();

        // null：默认按主键降序取第一条
        if (is_null($parameters)) {
            $parameters = [
                'conditions' => $obj->getSortDeleteColumnName() . ' IS NULL',
                'order' => $obj->getPrimaryKey() . ' DESC'
            ];
            if ($queryBuilder) {
                $builder = (new \Phalcon\Mvc\Model\Query\Builder())
                    ->from(static::class)
                    ->where($parameters['conditions'])
                    ->orderBy($parameters['order']);
                $queryBuilder($builder);
                return $builder->getQuery()->execute()->getFirst();
            }
            return parent::findFirst($parameters);
        }

        // 数字主键 → 参数化绑定，避免 SQL 注入
        if (is_numeric($parameters)) {
            $pk = $obj->getPrimaryKey();
            $conditions = $pk . ' = :_pk_: AND ' . $obj->getSortDeleteColumnName() . ' IS NULL';
            $parameters = ['conditions' => $conditions, 'bind' => ['_pk_' => intval($parameters)]];
            if ($queryBuilder) {
                $builder = (new \Phalcon\Mvc\Model\Query\Builder())
                    ->from(static::class)
                    ->where($conditions, ['_pk_' => intval($parameters['bind']['_pk_'])]);
                $queryBuilder($builder);
                return $builder->getQuery()->execute()->getFirst();
            }
            return parent::findFirst($parameters);
        }

        // 字符串 SQL 条件 + 软删除
        if (is_string($parameters)) {
            $params = $parameters . ' AND ' . $obj->getSortDeleteColumnName() . ' IS NULL';
            if ($queryBuilder) {
                $builder = (new \Phalcon\Mvc\Model\Query\Builder())
                    ->from(static::class)->where($params);
                $queryBuilder($builder);
                return $builder->getQuery()->execute()->getFirst();
            }
            return parent::findFirst($params);
        }

        // 数组：委托给 mergeParameters 追加软删除条件
        if (is_array($parameters)) {
            $parameters = self::mergeParameters($parameters, 1);
            if ($queryBuilder) {
                $builder = (new \Phalcon\Mvc\Model\Query\Builder())->from(static::class);
                if (is_string($parameters)) {
                    $builder->where($parameters);
                } else {
                    $builder->where($parameters['conditions'] ?? '1=1');
                    if (isset($parameters['bind'])) {
                        $builder->setBindParams($parameters['bind'], true);
                    }
                    if (isset($parameters['order'])) {
                        $builder->orderBy($parameters['order']);
                    }
                }
                $queryBuilder($builder);
                return $builder->getQuery()->execute()->getFirst();
            }
            return parent::findFirst($parameters);
        }

        throw new \Exception('parameters must be of type array,string,numeric or null');
    }

    /**
     * 查询全部的记录（含软删除）
     * @param $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws \Exception
     */
    public static function findWithTrashed($parameters = null)
    {
        return parent::find(self::mergeParameters($parameters, 0));
    }

    /**
     * 只查询软删除记录
     * @param $parameters null|string|numeric|array
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws \Exception
     */
    public static function findOnlyTrashed($parameters = null)
    {
        return parent::find(self::mergeParameters($parameters, -1));
    }

    /**
     * 记录(deleted_at IS NULL)的查询
     * @link https://docs.phalcon.io/5.0/en/db-models#find
     * @param $parameters string|numeric|array|null 查询参数 <pre>
     * find() // 查询全部记录
     * find(5) // 数字 查询主键 id=5 的记录
     * find('name="phx"') // 字符串，直接设置条件
     * find(["type = 'virtual'","order" => "name",]) // 原 find 查询方式，支持 columns/conditions/bind/order/limit 等条件
     * </pre>
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     * @throws \Exception
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find(self::mergeParameters($parameters, 1));
    }

    public static function query(\Phalcon\Di\DiInterface $container = null): \Phalcon\Mvc\Model\CriteriaInterface
    {
        /**
         * @var $obj \Phax\Mvc\Model
         */
        $obj = static::getObject();
        return parent::query($container)->andWhere($obj->getSortDeleteColumnName() . ' IS NULL');
    }

    public static function __callStatic(string $method, array $arguments)
    {
        if (str_starts_with($method, 'findBy')) {
            $name = AppService::helper()->uncamelize(substr($method, 6), '_');
            return self::find([
                $name . '= :key:', 'bind' => ['key' => $arguments[0]]
            ]);
        } elseif (str_starts_with($method, 'findFirstBy')) {
            $name = AppService::helper()->uncamelize(substr($method, 11), '_');

            return self::findFirst([
                $name => $arguments[0]
            ]);
        }
        return parent::__callStatic($method, $arguments);
    }


    /**
     * 拼接条件
     * @param $parameters mixed|null
     * @param $softDelete int -1 只要软删除；0 忽视；1 不要软删除
     * @return string|array
     * @throws \Exception
     */
    private static function mergeParameters(mixed $parameters = null, int $softDelete = 0): array|string
    {
        $obj = static::getObject();
        if (is_null($parameters)) {
            $items = [
                $obj->deletedTime . ' IS NOT NULL',
                null,
                $obj->deletedTime . ' IS NULL'
            ];
            return $items[$softDelete + 1];
        }
        if (is_numeric($parameters)) {
            $pk = $obj->getPrimaryKey();
            $items = [
                $pk . '=' . intval($parameters) . ' AND ' . $obj->deletedTime . ' IS NOT NULL',
                $pk . '=' . intval($parameters),
                $pk . '=' . intval($parameters) . ' AND ' . $obj->deletedTime . ' IS NULL',
            ];
            return $items[$softDelete + 1];
        }
        if (is_string($parameters)) {
            $items = [
                $parameters . ' AND ' . $obj->deletedTime . ' IS NOT NULL',
                $parameters,
                $parameters . ' AND ' . $obj->deletedTime . ' IS  NULL',
            ];
            return $items[$softDelete + 1];
        }
        if (is_array($parameters)) {
            // 复制数组，避免修改传入的原变量
            $params = $parameters;
            if (isset($params[0]) && is_string($params[0])) {
                if ($softDelete == -1) {
                    $params[0] = $params[0] . ' AND ' . $obj->deletedTime . ' IS NOT NULL';
                } elseif ($softDelete == 1) {
                    $params[0] = $params[0] . ' AND ' . $obj->deletedTime . ' IS NULL';
                }
            } elseif (isset($params['conditions'])) {
                if ($softDelete == -1) {
                    $params['conditions'] = $params['conditions'] . ' AND ' . $obj->deletedTime . ' IS NOT NULL';
                } elseif ($softDelete == 1) {
                    $params['conditions'] = $params['conditions'] . ' AND ' . $obj->deletedTime . ' IS NULL';
                }
            }
            return $params;
        }
        throw new \Exception('parameters must be of type array,string,numeric or null');
    }
}