<?php

namespace Phax\Helper;

use Phax\Db\Parameter;
use Phax\Foundation\AppService;

class ModelHelper
{
    /**
     * 定义 HasOne 一对一关联
     *
     * 如：一个 `User` 有一个 `Profile`，通过 `User.id → Profile.user_id` 关联。
     *
     * <pre>
     * // 在 User.php 模型中：
     * public function initialize(): void
     * {
     *     $this->hasMany(ModelHelper::hasOne(Profile::class, 'user_id'));
     * }
     * // 查询：$user->profile->avatar
     * </pre>
     *
     * @param string $referenceModel 必填。关联模型类名，如 `Profile::class`
     * @param string|array $referencedFields 必填。关联模型中的外键字段，如 `'user_id'`
     * @param string|array $fields 当前模型中关联所依赖的字段，默认 `'id'`
     * @param string $alias 查询别名，默认取 `$referenceModel` 的小写蛇形名，如 `profile`
     * @param bool $reusable 是否启用缓存。同一请求中多次访问同一条记录时可开启，默认 `true`
     * @param Parameter|null $params 额外的查询条件/排序/限制，如 `(new Parameter())->where('status', 1)`
     *
     * @return array 返回 Phalcon `$this->hasOne()` 可接收的关联参数数组
     * @link 完整示例 `src/App/Modules/demo/Models/User.php`
     *
     */
    public static function hasOne(
        string         $referenceModel,
        string|array   $referencedFields,

        string|array   $fields = 'id',
        string         $alias = '',
        bool           $reusable = true,
        Parameter|null $params = null,
    ): array
    {
        if (empty($alias)) {
            $alias = self::getAlias($referenceModel);
        }
        return [
            $fields,
            $referenceModel,
            $referencedFields,
            [
                'alias' => $alias,
                'reusable' => $reusable,
                'params' => $params?->getParameter(),
            ],
        ];
    }

    /**
     * 定义 HasMany 一对多关联
     *
     * 如：一个 `User` 有多个 `Article`，通过 `User.id → Article.user_id` 关联。
     *
     * <pre>
     * // 在 User.php 模型中：
     * public function initialize(): void
     * {
     *     $this->hasMany(ModelHelper::hasMany(Article::class, 'user_id'));
     * }
     * // 查询：$user->articles  → 返回结果集
     * </pre>
     *
     * @param string $referenceModel 必填。关联模型类名，如 `Article::class`
     * @param string|array $referencedFields 必填。关联模型中的外键字段，如 `'user_id'`
     * @param string|array $fields 当前模型中关联所依赖的字段，默认 `'id'`
     * @param string $alias 查询别名，默认取类名小写蛇形 + `s`，如 `articles`
     * @param bool $reusable 是否启用缓存，默认 `true`
     * @param Parameter|null $params 额外的查询条件/排序/限制
     *
     * @return array 返回 Phalcon `$this->hasMany()` 可接收的关联参数数组
     * @link 完整示例 `src/App/Modules/demo/Models/User.php`
     *
     */
    public static function hasMany(
        string         $referenceModel,
        string|array   $referencedFields,

        string|array   $fields = 'id',
        string         $alias = '',
        bool           $reusable = true,
        Parameter|null $params = null,
    ): array
    {
        if (empty($alias)) {
            $alias = self::getAlias($referenceModel) . 's';
        }
        return [
            $fields,
            $referenceModel,
            $referencedFields,
            [
                'alias' => $alias,
                'reusable' => $reusable,
                'params' => $params?->getParameter(),
            ],
        ];
    }

    /**
     * 定义 BelongsTo 多对一关联（HasMany 的反向）
     *
     * 如：多个 `Article` 属于一个 `User`，通过 `Article.user_id → User.id` 关联。
     *
     * <pre>
     * // 在 Article.php 模型中：
     * public function initialize(): void
     * {
     *     $this->belongsTo(ModelHelper::belongsTo(User::class));
     *     // fields 留空时会自动拼成 `user_id`（当前表字段），
     *     // 即 Article.user_id → User.id
     * }
     * // 查询：$article->user→nickname
     * </pre>
     *
     * @param string $referenceModel 必填。目标模型类名，如 `User::class`
     * @param string|array $fields 当前模型中的外键字段。
     *                                       留空时自动拼接：`user_alias + '_id'`，如 `'user_id'`
     * @param string|array $referencedFields 目标模型的主键字段，默认 `'id'`
     * @param string $alias 目标模型别名，默认取蛇形小写，如 `user`
     * @param bool $reusable 是否启用缓存，默认 `true`
     * @param Parameter|null $params 额外的查询条件/排序/限制
     *
     * @return array 返回 Phalcon `$this->belongsTo()` 可接收的关联参数数组
     */
    public static function belongsTo(
        string         $referenceModel,
        string|array   $fields = '',
        string|array   $referencedFields = 'id',
        string         $alias = '',
        bool           $reusable = true,
        Parameter|null $params = null,
    ): array
    {
        if (empty($alias)) {
            $alias = self::getAlias($referenceModel);
        }
        if (empty($fields)) {
            $fields = $alias . '_id';
        }
        return [
            $fields,
            $referenceModel,
            $referencedFields,
            [
                'alias' => $alias,
                'reusable' => $reusable,
                'params' => $params?->getParameter(),
            ],
        ];
    }



    /**
     * 定义 HasManyToMany 多对多关联
     *
     * 必须具有中间表。如：`users (1) ↔ (N) user_roles (N) ↔ (1) roles`
     *
     * <pre>
     * // 在 User.php 模型中：
     * public function initialize(): void
     * {
     *     $this->hasManyToMany(ModelHelper::hasManyToMany(
     *         UserRole::class,     // 中间表
     *         'user_id',           // 中间表关联当前表的字段
     *         Role::class          // 目标表
     *     ));
     * }
     * // 查询：$user->roles  → 返回角色结果集
     * // 关联链路：current_table.id ← intermediate_table.intermediateFields
     * //          → intermediate_table.intermediateReferencedFields → reference_table.referencedFields
     * </pre>
     * @param string $intermediateModel 必填。中间表模型类名，如 `UserRole::class`
     * @param array|string $intermediateFields 必填。中间表中关联当前表的字段，如 `'user_id'`
     * @param string $referenceModel 必填。目标模型类名，如 `Role::class`
     * @param string|array $referencedFields 目标表的主键字段，默认 `'id'`
     * @param array|string $intermediateReferencedFields 中间表中关联目标表的字段。
     *                                                     留空时自动拼接：`目标别名 + '_id'`，如 `'role_id'`
     * @param string|array $fields 当前模型的主键字段，默认 `'id'`
     * @param string $alias 别名，默认取目标类名小写蛇形 + `s`，如 `roles`
     * @param bool $reusable 是否启用缓存，默认 `true`
     * @param Parameter|null $params 额外的查询条件/排序/限制
     *
     * @return array 返回 Phalcon `$this->hasManyToMany()` 可接收的关联参数数组
     */
    public static function hasManyToMany(
        string         $intermediateModel,
        array|string   $intermediateFields,

        string         $referenceModel, // Role::class
        string|array   $referencedFields = 'id',
        array|string   $intermediateReferencedFields = '', // 自动设置为 role_id

        string|array   $fields = 'id',
        string         $alias = '', // 自动设置为 roles
        bool           $reusable = true,
        Parameter|null $params = null,
    ): array
    {
        if (empty($alias)) {
            $alias = self::getAlias($referenceModel) . 's';
        }
        if (empty($intermediateReferencedFields)) {
            $intermediateReferencedFields = self::getAlias($referenceModel) . '_id';
        }
        return [
            $fields,
            // 中间表
            $intermediateModel,
            $intermediateFields,
            $intermediateReferencedFields,
            // 目标模型
            $referenceModel,
            $referencedFields,
            // 其它
            [
                'alias' => $alias,
                'reusable' => $reusable,
                'params' => $params?->getParameter(),
            ],
        ];
    }
    /**
     * 获取模型类名的蛇形别名
     *
     * 将完整类名中的最后一段转为蛇形小写。
     *
     * 示例：
     * - `App\Models\UserProfile::class` → `user_profile`
     * - `App\Models\OrderItem::class`   → `order_item`
     *
     * @param string $referenceModel 模型类名（带命名空间）
     * @return string 蛇形小写的别名
     */
    public static function getAlias(string $referenceModel): string
    {
        $data = explode('\\', $referenceModel);
        return AppService::helper()->uncamelize(array_pop($data));
    }
}


