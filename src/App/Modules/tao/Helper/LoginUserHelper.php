<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Helper\Libs\MenuLibHelper;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemUser;

/**
 * 管理指定用户的访问权限/菜单
 */
class LoginUserHelper
{
    /**
     * 当前用户
     * @var SystemUser|null
     */
    private SystemUser|null $user = null;
    /**
     * 当前用户能够访问的节点列表
     * @var array|null
     */
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

    public function user(): SystemUser
    {
        if (empty($this->user)) {
            throw new \Exception('could not get user before you set user data');
        }
        return $this->user;
    }

    public function userId(): int
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
        $this->user->assign($info);
        $this->mvc->loginAuthHelper()->getAdapter()->saveUser($this->user);
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
        if (empty($this->user)) {
            throw new \Exception('用户未登录或登录信息失效');
        }
        if (in_array($this->user->id, $this->mvc->superAdminIds())) {
            return true;
        }

        return in_array($node, $this->getNodeList()) || in_array($node . '/index', $this->nodeList);
    }

    /**
     * 当前用户角色所能访问的节点列表，不包含用户节点
     * @return array ['ca1', 'ca2', 'ca3', ...]
     */
    public function getNodeList(): array
    {
        if (is_null($this->nodeList)) {
            $this->nodeList = $this->mvc->nodeService()->findNodeListByRoleIds($this->user->role_ids) ?: [];
        }
        return $this->nodeList;
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
        $row = SystemMenu::queryBuilder($this->mvc->getDi())
            ->columns(['title', 'icon', 'href', 'type', 'params'])
            ->int('pid', Data::HOME_PID)
            ->findFirstArray();
        if ($row) {
            if ($row['href']) {
                $row['href'] = $this->mvc->menuService()->href($row['href'], $row['type'], $row['params']);
            }
        }
        return $row;
    }

    /**
     * 获取用户操作菜单树
     * @return array
     * @throws \Exception
     */
    public function getMenuTree(): array
    {
        // 第一遍：纯树构建（不查权限）
        $systemMenus = SystemMenu::queryBuilder()
            ->int('status', 1)
            ->notEqual('pid', Data::HOME_PID)
            ->orderBy('pid asc, sort desc, id asc')
            ->findColumn('id, pid, title, icon, href, type, roles,params');
        $tree = MenuLibHelper::getSystemMenuTree(0, $systemMenus);
        // 第二遍：权限剪枝
        // 超级管理员不受节点权限限制
        $isSuperAdmin = in_array($this->user->id, $this->mvc->superAdminIds());
        $userNodes = $isSuperAdmin ? [] : $this->getNodeList();
        $userTree = MenuLibHelper::filterMenuTree($tree, $userNodes, $isSuperAdmin);
        // 处理 href（需要 MenuService，由调用方处理）
        return array_values(array_map(function ($menu) {
            return $this->processMenuHref($menu);
        }, $userTree));
    }

    /**
     * 递归处理菜单 href
     */
    private function processMenuHref(array $menu): array
    {
        if ($menu['href']) {
            $menu['href'] = $this->mvc->menuService()->href($menu['href'], $menu['type'], $menu['params']);
        }
        if (!empty($menu['child'])) {
            $menu['child'] = array_map(fn($c) => $this->processMenuHref($c), $menu['child']);
        }
        return $menu;
    }
}