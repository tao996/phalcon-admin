<?php

namespace App\Modules\tao\Helper\Libs;

use App\Modules\tao\Config\Data;

/**
 * 菜单工具：树构建 + 权限剪枝
 *
 * 纯静态方法，无任何外部依赖，便于 Mock 测试。
 * 数据来源和 href 处理由调用方负责。
 */
class MenuLibHelper
{
    /**
     * 纯树构建：按 pid 将一维菜单列表排成树结构
     *
     * @param int    $pid         当前父级 ID
     * @param array  $systemMenus 一维菜单列表，每项含 id, pid, roles, ...
     * @param string $defRoles    默认继承的父级角色（roles 为空时使用）
     * @return array 树形结构，每项含 child 字段
     */
    public static function getSystemMenuTree(int $pid, array $systemMenus, string $defRoles = ''): array
    {
        $tree = [];
        foreach ($systemMenus as $v) {
            if ($v['pid'] != $pid) {
                continue;
            }
            // 角色继承：如果菜单自身没设置 roles，则继承父级的
            if (empty($v['roles'])) {
                $v['roles'] = $defRoles;
            }
            $v['child'] = self::getSystemMenuTree($v['id'], $systemMenus, $v['roles']);
            $tree[$v['id']] = $v;
        }
        return $tree;
    }

    /**
     * 权限剪枝：递归遍历树，移除用户无权访问的节点
     *
     * @param array $menus       树形菜单（由 getSystemMenuTree 产出）
     * @param array $userNodes   用户可访问的节点列表（超级管理员传 []）
     * @param bool  $isSuperAdmin 当前用户是否为超级管理员
     * @return array 剪枝后的树
     */
    public static function filterMenuTree(array $menus, array $userNodes, bool $isSuperAdmin = false): array
    {
        $result = [];
        foreach ($menus as $id => $menu) {
            // 1. 递归剪枝子菜单
            if (!empty($menu['child'])) {
                $menu['child'] = self::filterMenuTree($menu['child'], $userNodes, $isSuperAdmin);
            }

            // 2. 判断当前菜单是否可见
            if (!self::isMenuVisible($menu, $userNodes, $isSuperAdmin)) {
                continue;
            }

            // 3. 过滤空容器：href 为空且无子菜单
            if (empty($menu['href']) && empty($menu['child'])) {
                continue;
            }

            $result[$id] = $menu;
        }
        return $result;
    }

    /**
     * 判断单条菜单是否对指定用户可见
     *
     * @param array $menu          菜单项（含 roles, href, ...）
     * @param array $userNodes     用户可访问的节点列表
     * @param bool  $isSuperAdmin  当前用户是否为超级管理员
     * @return bool
     */
    public static function isMenuVisible(array $menu, array $userNodes, bool $isSuperAdmin = false): bool
    {
        // 超级管理员 → 全部可见
        if ($isSuperAdmin) {
            return true;
        }
        // roles='user' 用户节点，用户总是可见
        if (Data::AccessUser == $menu['roles']) {
           return true;
        }
        // roles 为空 → 走节点检查
        if (empty($menu['roles'])) {
            if (empty($menu['href'])) {
                return true;
            }
            // 先精确匹配，再尝试补上 /index（控制器级 href 等价于默认 action）
            return in_array($menu['href'], $userNodes)
                || in_array($menu['href'] . '/index', $userNodes);
        }

        // 其他 role → 不可见
        return false;
    }
}
