<?php

declare(strict_types=1);

namespace Phax\Mvc;

use Phax\Db\Layer;
use Phax\Db\QueryBuilder;
use Phax\Support\Facade\MyHelperFacade;


/*
1. 修改器使用示例
class Cat extends Model {
    public function setTitleAttr($value)
    {
        $this->title = strtolower($value);
    }
}
$cat = new Cat();
$cat->title = 'HELLO'; // Cat 中没有 public title
dd($cat->title); // hello

2. 获取器使用示例
public function getStatusTextAttr()
{
    return 'active';
}
dd($cat->status_text); // active
 */

/**
 * 模型 <br>
 * 添加软删除 \Phax\Orm\SoftDelete 支持 find/findFirst/findByXx/findFirstByXx <br>
 * 添加修改器 setFieldNameAttr，只会在属性没有显示定义时被调用 <br>
 * 添加获取器 getFieldNameAttr，只会在属性没有显示定义时被调用 <br>
 * 添加搜索器 searchFieldNameAttr，在使用 withSearch 进触发 <br>
 * @method prepareSave() 在 Insert/Update 之前执行，允许进行数据管理
 * @method beforeValidation() 在数据验证之前执行
 * @method beforeValidationOnCreate()
 * @method beforeValidationOnUpdate()
 * @method validation() 在 insert/update 时进行数据验证, stop:yes
 * @method afterValidationOnCreate()
 * @method afterValidationOnUpdate()
 * @method afterValidation() 在 insert/update 时数据验证验证后执行
 * @method beforeSave() 在数据保存前执行
 * @method beforeCreate()
 * @method beforeUpdate()
 * @method afterCreate() 数据已经完成添加，此时可以获取主键
 * @method afterUpdate()
 * @method afterSave() 在数据保存成功后执行
 * @method afterDelete() 在数据保存成功后执行
 * @method beforeDelete()
 * @method notDeleted()
 * @method notSaved()
 * @method onValidationFails() stop:yes
 * @method self allowField(array $fields) 只允许为哪些字段的赋值
 * 注意：以下方法只在 SoftDelete 中存在
 * @method bool destroy(bool $force) 是否强制删除
 * @method bool restore() 恢复软删除数据
 * @method static \Phalcon\Mvc\Model\ResultsetInterface findWithTrashed($parameters = null) 查询全部的记录（含软删除）
 * @method static \Phalcon\Mvc\Model\ResultsetInterface findOnlyTrashed($parameters = null) 只查询软删除记录
 */
class Model extends \Phalcon\Mvc\Model
{
    /**
     * @var array 用于存放关系表
     * @link https://docs.phalcon.io/5.0/en/db-models-relationships
     */
    private static array $relationsMap = [];
    /**
     * 注意，需要自己手动写属性及其默认值
     */
    /**
     * @var string 数据库连接服务
     */
    public string $connection = 'db';
    /**
     * 表的前缀，通常用在 Module BaseModel 中，示例 demo_
     * @var string
     */
    protected string $tablePrefix = '';
    /**
     * @var string 设置表名,注意：不会自动添加前辍
     */
    protected string $table = '';

    /**
     * 允许为空值的字段（如数据表中的 text）
     * @var array
     */
    protected array $allowEmptyFields = [];

    /**
     * 模型表标题
     * @return string
     */
    public function tableTitle(): string
    {
        return 'todo:tableName';
    }

    /**
     * @var bool|string 自动写入时间戳，可选值： timestamp/datetime/int；<br>
     * 如果设置为 false 则关闭
     */
    protected string|bool $autoWriteTimestamp = 'int';
    /**
     * 创建时间字段名
     * @var string
     */
    protected string $createdTime = 'created_at';
    /**
     * 更新时间字段名
     * @var string
     */
    protected string $updatedTime = 'updated_at';
    /**
     * 定义软删除时间字段名，默认值必须设置为 NULL
     * @var string
     */
    protected string $deletedTime = 'deleted_at';

    public function getDeleteTimeName(): string
    {
        return $this->deletedTime;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    /**
     * 是否启用了软删除功能，可以通过在模型中使用 use Phax\Traits\SoftDelete 开启
     * @return bool
     */
    public function isSoftDelete(): bool
    {
        return property_exists($this, 'useSortDelete');
    }

    public function initialize(): void
    {
        $this->setConnectionService($this->connection);
        $table = $this->table ?: $this->getSource();
        if ($this->tablePrefix) {
            $table = $this->tablePrefix . $table;
        }
        $this->setSource($table);

        if ($this->autoWriteTimestamp) {
            if ($this->createdTime) {
                \Phax\Events\Model::timestampable(
                    $this,
                    'beforeValidationOnCreate',
                    $this->createdTime,
                    $this->autoWriteTimestamp
                );
            }

            if ($this->updatedTime) {
                \Phax\Events\Model::timestampable(
                    $this,
                    'beforeValidationOnCreate',
                    $this->updatedTime,
                    $this->autoWriteTimestamp
                );

                \Phax\Events\Model::timestampable(
                    $this,
                    'beforeValidationOnUpdate',
                    $this->updatedTime,
                    $this->autoWriteTimestamp
                );
            }
        }

        $this->useDynamicUpdate(true);

        if ($this->allowEmptyFields) {
            $this->allowEmptyStringValues($this->allowEmptyFields);
        }
    }

    /**
     * 获取错误信息列表
     * @param bool $first 是否返回第一個錯誤
     * @return array|string
     */
    public function getErrors(bool $first = false): array|string
    {
        $messages = [];
        foreach ($this->getMessages() as $message) {
            if ($first) {
                return $message->getMessage();
            }
            $messages[] = $message->getMessage();
        }
        return $first && empty($messages) ? '' : $messages;
    }

    public function getFirstError(): string
    {
        return $this->getErrors(true);
    }

    /**
     * 获取调用者信息
     * <code>
     * class User extends Model {
     *     public int $id = 0;
     *     public function articles()
     *          return $this->hasManyPhx(Article::class);
     *          // getRelatedCaller() will return [
     *          //      "key" => "/var/www/App/Modules/demo/Models/User.php.articles",
     *          //      "alias" => "articles",
     *          //      "fk" => "user_id",
     *          //      "method" => "getArticles"
     *          // ]
     *     }
     * }
     * class Article extends Model {
     *     public int $id = 0;
     *     public int $user_id = 0;
     * }
     * </code>
     * @return array{key:string,alias:string,fk:string,method:string} ['key'=>'识别 id', 'alias'=>'名称', 'fk'=>'外键']
     */
    protected function getRelatedCaller(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $alias = $trace[2]['function'];
        $key = $trace[1]['file'] . '.' . $alias;
        $nameParts = explode('\\', get_class($this));
        $foreignKey = $this->getDI()->get('helper')->uncamelize(array_pop($nameParts)) . '_id';
        return ['key' => $key, 'alias' => $alias, 'fk' => $foreignKey, 'method' => 'get' . ucwords($alias)];
    }

    /**
     * 一对多关系，示例 every User has many Article
     * <code>
     * // see example: src/App/Modules/demo/A0/db/Controllers/TestController.php indexAction
     * class User extends DemoBaseModel {
     *     public int $id = 0;
     *     public function articles(){
     *         return $this->hasManyPhx(Article::class);
     *     }
     * }
     * class Article extends DemoBaseModel {
     *     public int $id = 0;
     *     public int $user_id = 0;
     * }
     * $user->articles; // get user articles records
     * </code>
     * @param string $referenceModel 关联模型 示例：Article::class
     * @param array $options 配置 <pre>[
     *   'localKey'=>当前模型(User)主键，默认的为 id,
     *   'foreignKey'=>关联表(Article)外键，名+_id, 示例：user_id,
     *   'params'=> 其它条件 ['conditions'=>string, 'bind'=>array, 'order'=>string]
     * ]</pre>
     * @return \Phalcon\Mvc\Model\Resultset
     */
    protected function hasManyPhx(string $referenceModel, array $options = [])
    {
        $info = $this->getRelatedCaller();
//        dd($info);
        if (!isset(static::$relationsMap[$info['key']])) {
            static::$relationsMap[$info['key']] = true;
            $localKey = $options['localKey'] ?? 'id';
            $foreignKey = $options['foreignKey'] ?? $info['fk'];
//            dd('hasManyPhx', $localKey, $foreignKey);
            $this->hasMany($localKey, $referenceModel, $foreignKey, [
                'alias' => $info['alias'],
                'reusable' => true,
                'params' => $options['params'] ?? [],
            ]);
        }
        return $this->{$info['method']}();
    }

    /**
     * 定义多对多的关系，以 User(id) => UserRole(user_id, role_id) => Role(id) 为例
     * <code>
     * // example: // see example: src/App/Modules/demo/A0/db/Controllers/TestController.php
     * public function roles() {
     *   return $this->hasManyToManyPhx(Role::class,UsersRoles::class);
     * }
     * $user->roles(); // get user role records
     * </code>
     * @param string $referenceModel 关联模型类名 Role::Class
     * @param string $intermediateModel 中间表模型 UserRole::Class
     * @param array $options 配置信息 <pre>[
     *  'intermediateFields' => 当前模型 User 在中间表 UserRole 中的外键，默认为当前模型+_id；如 user_id, <br>
     *  'intermediateReferencedFields' => 关联模型 Role 在中间表 UserRole 中的外键，默认为关联模型+_id；如 role_id, <br>
     *  'fields' => 当前模型 User 主键，默认为 id, <br>
     *  'referencedFields' => 关联模型 Role 主键，默认为 id, <br>
     *  'params'=> 其它条件 ['conditions'=>string, 'bind'=>array, 'order'=>string], <br>
     * ]</pre>
     * @return \Phalcon\Mvc\Model\Resultset
     */
    protected function hasManyToManyPhx(string $referenceModel, string $intermediateModel, array $options = [])
    {
        /* 原生写法
        $this->hasManyToMany(
            'id', UsersRoles::class,
            'user_id', 'role_id',
            Role::class, 'id',
            ['alias' => 'roles']
        );
         */
        $info = $this->getRelatedCaller();

        if (!isset(static::$relationsMap[$info['key']])) {
            static::$relationsMap[$info['key']] = true;
            $intermediateReferencedFields = $options['intermediateReferencedFields'] ?? '';
            if ('' == $intermediateReferencedFields) {
                $tmp = explode('\\', $referenceModel);
                $intermediateReferencedFields = strtolower(end($tmp)) . '_id';
            }
            parent::hasManyToMany(
                $options['fields'] ?? 'id',
                $intermediateModel,
                $options['intermediateFields'] ?? $info['fk'],
                $intermediateReferencedFields,
                $referenceModel,
                $options['referencedFields'] ?? 'id',
                [
                    'alias' => $info['alias'],
                    'reusable' => true,
                    'params' => $options['params'] ?? [],
                ]
            );
        }
        return $this->{$info['method']}();
    }

    /**
     * 一对一关系，示例 every User has one Profile <br>
     * <pre>public function profile() {
     *   return $this->hasOnePhx(Profile::class);
     * }
     * $user->profile; // get user profile
     * </pre>
     * @param string $referenceModel Profile::class
     * @param array $options 配置 <pre>[
     *   'localKey'=>当前模型主键，默认的为 id,
     *   'foreignKey'=>外键，默认的外键名为当前模型名+_id, 示例：user_id,
     *   'params'=> 其它条件 ['conditions'=>string, 'bind'=>array, 'order'=>string]
     * ]</pre>
     * @return \Phalcon\Mvc\Model
     */
    protected function hasOnePhx(string $referenceModel, array $options = [])
    {
        $info = $this->getRelatedCaller();
        if (!isset(static::$relationsMap[$info['key']])) {
            static::$relationsMap[$info['key']] = true;
            $this->hasOne($options['localKey'] ?? 'id', $referenceModel, $options['foreignKey'] ?? $info['fk'], [
                'alias' => $info['alias'],
                'reusable' => true,
                'params' => $options['params'] ?? [],
            ]);
        }
        return $this->{$info['method']}();
    }

    /**
     * 为了仿 TP 中关联属性用法
     * @param string $property
     * @return mixed
     */
    public function __get(string $property)
    {
//dd($property,'get' . MyHelper::pascalCase($property) . 'Attr');
        if (method_exists($this, $property)) {
            // $user->profile 时调用 profile 中定义的模型
            $this->{$property}();
            // 定义获取器
        } elseif (method_exists($this, 'get' . MyHelperFacade::pascalCase($property) . 'Attr')) {
            $method = 'get' . MyHelperFacade::pascalCase($property) . 'Attr';
            return $this->{$method}();
        }
        return parent::__get($property);
    }

    public function __set(string $property, $value)
    {
        // 存在修改器
        if (method_exists($this, 'set' . ucfirst($property) . 'Attr')) {
            call_user_func_array([
                $this,
                'set' . ucfirst($property) . 'Attr'
            ], [$value]);
            return;
        }
        parent::__set($property, $value);
    }

    /**
     * 获取当前实例(用于获取实例的各种原始信息)，注意不要修改实例的属性
     * @return static
     */
    public static function getObject(): \Phax\Mvc\Model|self
    {
        static $obj = [];
        $called = get_called_class();
        if (!isset($obj[$called])) {
            $obj[$called] = new static();
        }
//        pr('------',get_class(new static()),get_called_class(),false);
        return $obj[$called];
    }


    /**
     * 获取主键，用于拼接 builder
     * @return string
     * @throws \Exception
     */
    public function getPrimaryKey(): string
    {
        $mt = $this->getModelsMetaData();
        $pks = $mt->getPrimaryKeyAttributes($this);
        if (empty($pks)) {
            throw new \Exception('not primary key exits in the model');
        }
        return end($pks);
    }

    /**
     * 获取指定列名的 PDO 类型
     * @param string $name
     * @return int 绑定类型
     */
    public function getDataTypeBinds(string $name, bool $throwIfNotfound = true): int
    {
        static $binds = [];
        $className = static::class;
        if (!isset($binds[$className])) {
            $binds[$className] = $this->getModelsMetaData()->getBindTypes($this);
        }

        if (isset($binds[$className][$name])) {
            return $binds[$className][$name];
        }
        if ($throwIfNotfound) {
            throw new \Exception('could not find the bind type for ' . $name . ' in the model ' . get_class($this));
        }
        return 0;
    }

    /**
     * 辅助拼接 Phalcon SQL 语句；注意需要手动添加 softDelete()
     * @return \Phax\Db\QueryBuilder
     */
    public static function queryBuilder(bool $excludeSoftDelete = true): QueryBuilder
    {
        return QueryBuilder::with(static::getObject(), $excludeSoftDelete);
    }

    /**
     * 用于使用原生SQL来执行 Insert/Update/Delete 操作
     * @return Layer
     * @throws \Exception
     */
    public static function layer(): Layer
    {
        return Layer::with(static::getObject());
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return QueryBuilder::with($this, $this->isSoftDelete());
    }
    /**
     * 用于使用原生SQL来执行 Insert/Update/Delete 操作
     * @return Layer
     * @throws \Exception
     */
    public function getLayer(): Layer
    {
        return Layer::with($this);
    }


    /**
     * 查询第一条记录
     * @param mixed $parameters 查询条件示例
     * <pre>
     * 5 // 单独一个 ID
     * 'uuid = "abc"' 或者 'inv_id = 3' 或者 ['inv_id = 3']
     * ['conditions' => 'inv_id = :id:','bind'=> ['id' => 4,]]
     * ['uuid = ?0','bind' => [$uuid]]
     * ['uuid = :uuid:','bind' => ['uuid' => $uuid]]
     * ['conditions'=>'', 'columns'=>'', 'bind'=>[], 'bindTypes'=>[], 'order'=>'',]
     * 注意：不支持 ['id'=>1, 'user_id'=>2]
     * </pre>
     * @return \Phalcon\Mvc\Model\Row|\Phalcon\Mvc\ModelInterface
     * @throws \Exception
     */
    public static function mustFindFirst($parameters): static
    {
        if ($record = static::findFirst($parameters)) {
            return $record;
        }
        throw new \Exception('找不到符合要求的记录');
    }

    /**
     * 更新指定的列值（not trigger model beforeXxx events）
     * <code>
     * $user->age = 100;
     * $user->updateColumns('age');
     * </code>
     * @param array|string $columns 待更新的列
     * @param string $primaryKeyName 主键/唯一键，默认为 id
     * @return bool
     */
    public function updateColumns(array|string $columns, string $primaryKeyName = 'id'): bool
    {
        if (empty($columns)) {
            return $this->update();
        } else {
            $pkv = $this->$primaryKeyName;
            if (empty($pkv)) {
                throw new \Exception('primary key value is empty when update the columns');
            }
            if (is_string($columns)) {
                $columns = explode(',', $columns);
            } else {
                $keys = array_keys($columns);
                if (count(array_filter($keys, 'is_numeric')) != count($keys)) {
                    throw new \Exception('updateColumns not support ["k"=>"v"]');
                }
            }
            $data = $this->toArray($columns);
            return $this->getWriteConnection()->update(
                $this->getSource(),
                array_keys($data),
                array_values($data),
                $primaryKeyName . '=' . $pkv
            );
        }
    }

    /**
     * 清空数据表
     * @param bool $confirm 必须 === true 才能生效
     * @return bool
     * @throws \Exception
     */
    public static function truncate(bool $confirm): bool
    {
        if ($confirm !== true) {
            throw new \Exception('confirm must be true');
        }
        $obj = self::getObject();
        $sql = 'TRUNCATE TABLE `' . $obj->getSource() . '`';
        return $obj->getWriteConnection()->execute($sql);
    }

    private array $toArrayColumns = [
        'append' => [],
        'visible' => [],
        'hidden' => [],
    ];

    /**
     * TODO 追加获取器(关联属性）到模型中
     * @param array $attrs
     * @return self
     */
    public function append(array $attrs): static
    {
        $this->toArrayColumns['append'] = $attrs;
        return $this;
    }

    /**
     * 只显示指定的列值，也可以在 toArray 时指定
     * @param array $columns
     * @return self
     */
    public function visible(array $columns): static
    {
        $this->toArrayColumns['visible'] = $columns;
        return $this;
    }

    /**
     * 隐藏指定的列值
     * @param array $columns
     * @return self
     */
    public function hidden(array $columns): static
    {
        $this->toArrayColumns['hidden'] = $columns;
        return $this;
    }

    /**
     * @param $columns
     * @param $useGetter
     * @return array 不保存返回数组中 key 与 $columns 的顺序一致
     */
    public function toArray($columns = null, $useGetter = null): array
    {
        if (is_null($columns)) {
            if (!empty($this->toArrayColumns['visible'])) {
                $columns = $this->toArrayColumns['visible'];
            }
        }
        if (!empty($this->toArrayColumns['hidden'])) {
            if (is_null($columns)) {
                $columns = $this->getModelsMetaData()->getAttributes($this);
            }
            $columns = array_diff($columns, $this->toArrayColumns['hidden']);
        }
        return parent::toArray($columns, $useGetter);
    }

}