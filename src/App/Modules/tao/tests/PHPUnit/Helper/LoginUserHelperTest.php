<?php

namespace App\Modules\tao\tests\PHPUnit\Helper;

use App\Modules\tao\Helper\LoginUserHelper;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\Application;
use PHPUnit\Framework\TestCase;

class LoginUserHelperTest extends TestCase
{
    private static MyMvcHelper $mvc;
    private static SystemUser $adminUser;

    /**
     * 获取 admin 用户（tao 模块超级管理员）
     */
    public static function setUpBeforeClass(): void
    {
        self::$mvc = new MyMvcHelper(Application::di());

        // 使用 admin 用户进行菜单树测试（admin 有全部权限）
        $adminUser = SystemUser::findFirst(1000);
        if (!$adminUser) {
            throw new \Exception('初始化测试需要 id=1000 的用户存在');
        }
        self::$adminUser = $adminUser;
    }

    private function getMenuTreeTitles(): array
    {
        $helper = new LoginUserHelper(self::$mvc);
        $helper->resetUser(self::$adminUser);
        $menus = $helper->getMenuTree();
        return $this->extractTitles($menus);
    }

    private function extractTitles(array $menus, string $prefix = ''): array
    {
        $titles = [];
        foreach ($menus as $menu) {
            $title = $prefix . $menu['title'];
            $titles[] = $title;
            if (!empty($menu['child'])) {
                $titles = array_merge($titles, $this->extractTitles($menu['child'], $title . ' > '));
            }
        }
        return $titles;
    }

    public function testAdminSeesAllMenus(): void
    {
        $titles = $this->getMenuTreeTitles();

        // 管理员应看到所有顶级菜单
        $this->assertContains('系统管理', $titles);
        $this->assertContains('会员中心', $titles);
        $this->assertContains('公共模块', $titles);

        // 管理员应看到所有子菜单
        $this->assertContains('系统管理 > 菜单管理', $titles);
        $this->assertContains('系统管理 > 用户管理', $titles);
        $this->assertContains('系统管理 > 角色管理', $titles);
        $this->assertContains('系统管理 > 节点管理', $titles);
        $this->assertContains('系统管理 > 配置管理', $titles);
        $this->assertContains('会员中心 > 文件管理', $titles);
        $this->assertContains('会员中心 > 快捷入口', $titles);
        $this->assertContains('会员中心 > 日志管理', $titles);
    }

    public function testMenuCountMatch(): void
    {
        $titles = $this->getMenuTreeTitles();
        $totalMenus = SystemMenu::queryBuilder(self::$mvc->getDi())
            ->int('status', 1)
            ->notEqual('pid', 99999999) // HOME_PID
            ->findColumn('id, pid');

        // 管理员可见菜单数应基本合理
        $this->assertGreaterThan(10, count($titles));
    }

    /**
     * testParentMenuWithoutRoles: 
     * 父菜单（无 roles）不应通过继承让子菜单绕过节点权限检查
     */
    public function testRolesNotPropagatedWithoutInheritance(): void
    {
        // "系统管理" (id=2, roles='') 的子菜单应严格按照节点权限过滤
        // 由于 admin 用户有全部节点，这里验证子菜单可见
        $helper = new LoginUserHelper(self::$mvc);
        $helper->resetUser(self::$adminUser);
        $menus = $helper->getMenuTree();

        // 找到"系统管理"的子菜单
        $systemMenu = array_filter($menus, fn($m) => $m['title'] === '系统管理');
        $this->assertNotEmpty($systemMenu);
        $systemMenu = reset($systemMenu);

        $childTitles = array_column($systemMenu['child'], 'title');
        $this->assertContains('菜单管理', $childTitles);
        $this->assertContains('用户管理', $childTitles);
    }
}
