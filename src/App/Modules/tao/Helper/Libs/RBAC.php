<?php

namespace App\Modules\tao\Helper\Libs;

/**
 * RBAC 权限标记 Attribute
 *
 * 替代 PHPDoc 中的 @rbac 注解，支持 PHP 8 原生语法。
 * 可标记在类或方法上，支持重复标记（IS_REPEATABLE）。
 *
 * 使用示例：
 * ```php
 * use App\Modules\tao\Helper\Libs\RBAC;
 *
 * #[RBAC(title:'系统节点管理')]
 * class NodeController extends BaseController
 * {
 *     #[RBAC(title:'更新节点')]
 *     public function reloadAction($todb = false)
 * ```
 *
 * 参数说明：
 * - title: 节点显示名称
 * - close: 设置为 1 表示关闭 RBAC 权限控制（= 不加入权限管理）
 * - scope: 作用域标记（预留扩展）
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RBAC
{
    public function __construct(
        public string $title = '',
        public int    $close = 0,
        public int    $scope = 0,
    ) {
    }
}
