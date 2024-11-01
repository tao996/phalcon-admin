<?php

namespace Phax\Traits;

use Phax\Support\Facade\MyHelperFacade;

/**
 * 软删除：重写了 findFirst/find/query/findByXxx, findFirstByXxx
 */
trait SoftDelete
{
    public $useSortDelete = true;

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
     * findFirst(5) // 数字 查询主键 id=5 的记录 <br>
     * findFirst('name="phx"') // 字符串，直接设置条件<br>
     * findFirst(['name'=>'phx', 'age'=>5]) // 使用绑定方式查询 name='phx' AND age=5 的记录
     * @param callable|null $queryBuilder \Phalcon\Mvc\Model\Query\Builder()
     * @throws \Exception
     * @return \Phalcon\Mvc\Model\Row|\Phalcon\Mvc\Model|self|null
     */
    public static function findFirst($parameters = null, callable $queryBuilder = null): mixed
    {
        /**
         * @var $obj \Phax\Mvc\Model
         * @var $builder \Phalcon\Mvc\Model\Query\Builder
         */
        $obj = static::getObject();
        $builder = new \Phalcon\Mvc\Model\Query\Builder();
        $builder->from(__CLASS__);
        $builder->where($obj->getDeleteTimeName() . ' IS NULL');
        if (is_null($parameters)) {
            $builder->orderBy($obj->getPrimaryKey() . ' DESC');
        } elseif (is_numeric($parameters)) {
            $builder->andWhere($obj->getPrimaryKey() . ' = ' . $parameters);
        } elseif (is_string($parameters)) {
            $builder->andWhere($parameters);
        } elseif (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $builder->andWhere($key . ' = :' . $key . ':', [
                    $key => $value
                ]);
            }
        } else {
            throw new \Exception('parameters must be of type array,string,numeric or null');
        }
        if (!is_null($queryBuilder)) {
            $queryBuilder($builder);
        }
//        $builder->limit(1);
//        dd($builder->getQuery()->getSql(),$builder->getQuery()->getBindParams());
        /**
         * @var $results \Phalcon\Mvc\Model\Resultset
         */
        $results = $builder->getQuery()->execute();
//        dd($results,$results->getFirst());
        return $results?->getFirst();
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
        return parent::query($container)->andWhere($obj->getDeleteTimeName() . ' IS NULL');
    }

    public static function __callStatic(string $method, array $arguments)
    {
        if (str_starts_with($method, 'findBy')) {
            $name = MyHelperFacade::uncamelize(substr($method, 6), '_');
            return self::find([
                $name . '= :key:', 'bind' => ['key' => $arguments[0]]
            ]);
        } elseif (str_starts_with($method, 'findFirstBy')) {
            $name = MyHelperFacade::uncamelize(substr($method, 11), '_');

            return self::findFirst([
                $name => $arguments[0]
            ]);
        }
        return parent::__callStatic($method, $arguments);
    }


    /**
     * 拼接条件
     * @param $parameters mixed
     * @param $softDelete int -1 只要软删除；0 忽视；1 不要软删除
     * @return string|array
     * @throws \Exception
     */
    private static function mergeParameters($parameters = null, $softDelete = 0)
    {
        $obj = static::getObject();
        if (is_null($parameters)) {
            $items = [
                $obj->deletedTime . ' IS NOT NULL',
                null,
                $obj->deletedTime . ' IS NULL'
            ];
        } elseif (is_numeric($parameters)) {
            $items = [
                $obj->getPrimaryKey() . '=' . $parameters . ' AND ' . $obj->deletedTime . ' IS NOT NULL',
                $obj->getPrimaryKey() . '=' . $parameters,
                $obj->getPrimaryKey() . '=' . $parameters . ' AND ' . $obj->deletedTime . ' IS NULL',
            ];
        } elseif (is_string($parameters)) {
            $items = [
                $parameters . ' AND ' . $obj->deletedTime . ' IS NOT NULL',
                $parameters,
                $parameters . ' AND ' . $obj->deletedTime . ' IS  NULL',
            ];
        } elseif (is_array($parameters)) {
            if (isset($parameters[0]) && is_string($parameters[0])) {
                if ($softDelete == -1) {
                    $parameters[0] = $parameters[0] . ' AND ' . $obj->deletedTime . ' IS NOT NULL';
                } elseif ($softDelete == 1) {
                    $parameters[0] = $parameters[0] . ' AND ' . $obj->deletedTime . ' IS NULL';
                }
            } elseif (isset($parameters['conditions'])) {
                if ($softDelete == -1) {
                    $parameters['conditions'] = $parameters['conditions'] . ' AND ' . $obj->deletedTime . ' IS NOT NULL';
                } elseif ($softDelete == 1) {
                    $parameters['conditions'] = $parameters['conditions'] . ' AND ' . $obj->deletedTime . ' IS NULL';
                }
            }
            return $parameters;
        } else {
            throw new \Exception('parameters must be of type array,string,numeric or null');
        }
        return $items[$softDelete + 1];
    }
}