<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemUser;

/**
 * 管理指定用户的访问权限/菜单
 */
class LoginUserHelper
{
    private SystemUser|null $user = null;
    private array|null $nodeList = null;

    public function __construct(public MyMvcHelper $mvc)
    {
    }

    public function resetUser(SystemUser|null $user): static
    {
        if (empty($user) || $user->id < 1) {
            throw new \Exception('必须指定用户 setUser');
        }
        $this->user = $user;
        return $this;
    }

    public function user():SystemUser
    {
        return $this->user;
    }

    public function userId():int
    {
        return is_null($this->user) ? 0 : $this->user->id;
    }

    public function isSuperAdmin(): bool
    {
        return $this->mvc->userService()->isSuperAdmin($this->user);
    }
    /**
     * 更新用户信息
     * @param array $info
     * @return void
     * @throws \Exception
     */
    public function updateUserInfo(array $info = []): void
    {
        $this->mvc->loginAuthHelper()->getAdapter()->saveUser(
            $info
                ? array_merge($this->user->toArray(), $info)
                : $this->user->toArray()
        );
        $this->user->assign($info);
    }

    /**
     * 重新加载用户信息
     * @throws \Exception
     */
    public function reloadUserInfo(): void
    {
        $this->mvc->loginAuthHelper()->loginWith($this->userId());
    }

    /**
     * 当前用户能否访问指定的节点
     * @param string $node 待检查的节点
     * @return bool
     * @throws \Exception
     */
    public function access(string $node): bool
    {
        if (empty($node)) {
            throw new \Exception('待检查的节点不能为空');
        }

        if (in_array($this->user->id, $this->mvc->superAdminIds())) {
            return true;
        }

        return in_array($node, $this->getNodeList());
    }

    /**
     * 当前用户角色所能访问的节点列表
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public function getNodeList(): array
    {
        if (is_null($this->nodeList)) {
            $this->nodeList = $this->mvc->nodeService()->findNodeListByRoleIds($this->user->role_ids);
        }
        return $this->nodeList ?: [];
    }

    /**
     * 是否在指定角色中
     * @param array $roles 支持字符串（角色名）数组或角色ID
     * @return bool
     * @throws \Exception
     */
    public function inRoles(array $roles): bool
    {
        if (empty($roles)) {
            throw new \Exception('待检查的角色不能为空');
        }
        if (!is_integer(end($roles))) {
            $roles = $this->mvc->roleService()->getIds($roles);
        }
        return !empty(array_intersect($this->user->roleIds(), $roles));
    }

    /**
     * 获取用户首页操作菜单列表
     * @return array
     * @throws \Exception
     */
    public function getHomeInfo(): array
    {
        return SystemMenu::queryBuilder()->columns(['title', 'icon', 'href', 'type', 'params'])
            ->int('pid', Data::HOME_PID)
            ->findFirstArray(function (&$row) {
                if ($row['href']) {
                    $row['href'] = $this->mvc->menuService()->href($row['href'], $row['type'], $row['params']);
                }
            });
    }

    /**
     * 获取用户操作菜单树
     * @return array
     * @throws \Exception
     */
    public function getMenuTree(): array
    {
        // 节点的数据来自 tao_system_node
        $userNodes = $this->getNodeList();
        // 系统菜单来自用户自定义,可能带有 /m/, /p/
        $systemMenus = SystemMenu::queryBuilder()
            ->int('status', 1)
            ->notEqual('pid', Data::HOME_PID)
            ->orderBy('sort desc, id asc')
            ->findColumn('id, pid, title, icon, href, type, roles,params');
        $menus = $this->buildMenuChild(0, $systemMenus, $userNodes, '');
        // 过滤掉空节点的一级菜单
        return array_values(array_filter($menus, function ($menu) {
            return $menu['pid'] == 0 && !empty($menu['child']);
        }));
    }


    private function buildMenuChild(int $pid, array $menuList, array $nodes, string $defRole = ''): array
    {
        $treeList = [];
        foreach ($menuList as $v) {
            if ($pid != $v['pid']) {
                continue;
            }
            $check = false;
            while (true) {
                if (in_array($this->user->id, $this->mvc->superAdminIds())) {
                    $check = true;
                    break;
                }
                if (empty($v['roles'])) {
                    $v['roles'] = $defRole;
                }
                if ($v['roles']) {
                    if (Data::AccessUser == $v['roles']) {
                        $check = true;
                    }
                    break;
                }

                $check = empty($v['href']) || in_array($v['href'], $nodes);
                break;
            }

            if (!$check) {
                continue;
            }
            $v['href'] = $this->mvc->menuService()->href($v['href'], $v['type'], $v['params']);

            $node = $v;
            $node['child'] = $this->buildMenuChild($v['id'], $menuList, $nodes, $v['roles']);
            $treeList[] = $node;
        }
        return $treeList;
    }
}