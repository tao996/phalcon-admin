<?php

namespace Phax\Helper;

use Phax\Db\Parameter;
use Phax\Foundation\AppService;

class ModelHelper
{
    /**
     * HasOne 在模型的 `initialize` 方法中定义一对一关联
     * @link 使用示例 `src/App/Modules/demo/Models/User.php`
     * 定义 User 有一个 Profile，通过 Users.id 关联到 Profile.user_id。 <br>
     * `$referenceModel` 必填，被引用模型的类型 `Profile::class`; <br>
     * `$referencedFields` 必填，被引用模型 `Profile` 的字段, `user_id`; <br>
     * `$fields` User 模型字段，默认为 `id`; <br>
     * `$alias` 查询别名，默认为 `$referenceModel` 小写以方便查询 `profile`; <br>
     * `$reusable` 是否开启缓存，如果多次查询同一条记录，则可以开启，默认为 `true`; <br>
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
     * HasMany 在模型的 `initialize` 方法中定义一对多关联
     * @link 使用示例 `src/App/Modules/demo/Models/User.php`
     * 定义 User 有多个 Article，通过 Users.id 关联到 Article.user_id。 <br>
     * `$referenceModel` 必填，被引用模型的类型 `Article::class`; <br>
     * `$referencedFields` 必填，被引用模型 `Article` 的字段, `user_id`; <br>
     * `$fields` User 模型字段，默认为 `id`; <br>
     * `$alias` 别名，默认为类名小写+s 如 `articles`; <br>
     * `$reusable` 是否开启缓存，如果多次查询同一条记录，则可以开启，默认为 `true`; <br>
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

    public static function getAlias(string $referenceModel): string
    {
        $data = explode('\\', $referenceModel);
        return AppService::helper()->uncamelize(array_pop($data));
    }

    /**
     * 定义多对多的关系，必须具有一个中间表，如 `users (1) <-> (N) user_roles (N) <-> (1) roles` <br>
     *  `$intermediateModel` 必填，中间表，如 `UserRole::class` <br>
     *  `$intermediateFields` 中间表关联前表的字段名称，这里为 `user_id` <br>
     *  `$intermediateReferencedFields` 中间表关联查询表的字段名称，这里为 `role_id` <br><br>
     *  `$referenceModel` 必填，目标模型名称 `Role::class`; <br>
     *  `$referencedFields` 目标模型与中间表关联字段名称, `id`; <br>
     *  `$fields` 当前模型与中间表字关联的字段名称，默认为 `id`; <br>
     *  `$alias` 别名，默认目标类名小写+s 如 `articles`; <br>
     *  `$reusable` 是否开启缓存，如果多次查询同一条记录，则可以开启，默认为 `true`; <br>
     * @return array
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
}


