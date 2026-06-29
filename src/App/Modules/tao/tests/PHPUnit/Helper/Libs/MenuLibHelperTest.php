<?php

namespace App\Modules\tao\tests\PHPUnit\Helper\Libs;

use App\Modules\tao\Helper\Libs\MenuLibHelper;
use PHPUnit\Framework\TestCase;

class MenuLibHelperTest extends TestCase
{
    // ============================================================
    //  场景 1：超级管理员 → 全部可见
    // ============================================================

    public function testSuperAdminSeesAll(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '系统管理', 'href' => '', 'roles' => ''],
            ['id' => 2, 'pid' => 1, 'title' => '用户管理', 'href' => 'tao/admin.user', 'roles' => ''],
            ['id' => 3, 'pid' => 1, 'title' => '角色管理', 'href' => 'tao/admin.role', 'roles' => ''],
        ];
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, [], true);

        $this->assertCount(1, $result); // 只有顶级
        $this->assertEquals('系统管理', $result[1]['title']);
        $this->assertCount(2, $result[1]['child']);
    }

    // ============================================================
    //  场景 2：普通用户，部分节点匹配 → 只显示有权限的菜单
    // ============================================================

    public function testUserWithPartialNodes(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '系统管理', 'href' => '', 'roles' => ''],
            ['id' => 2, 'pid' => 1, 'title' => '用户管理', 'href' => 'tao/admin.user', 'roles' => ''],
            ['id' => 3, 'pid' => 1, 'title' => '角色管理', 'href' => 'tao/admin.role', 'roles' => ''],
            ['id' => 4, 'pid' => 0, 'title' => '会员中心', 'href' => '', 'roles' => 'user'],
            ['id' => 5, 'pid' => 4, 'title' => '文件管理', 'href' => 'tao/user.uploadfile', 'roles' => ''],
            ['id' => 6, 'pid' => 4, 'title' => '日志管理', 'href' => 'tao/user.log', 'roles' => ''],
        ];
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
//        ddd($tree);
        $this->assertCount(2, $tree[1]['child']);
        $this->assertCount(2, $tree[4]['child']);
        // 如果子节点角色为空，则子节点会继续父节点的角色
        $this->assertEquals('user', $tree[4]['child'][5]['roles']);
        $this->assertEquals('user', $tree[4]['child'][6]['roles']);

        $userNodes = ['tao/admin.user'];
        $result = MenuLibHelper::filterMenuTree($tree, $userNodes, false);
        $this->assertEquals('用户管理', $result[1]['child'][2]['title']);
        // 用户中心
        $this->assertEquals(2, count($result[4]['child']));
    }

    // ============================================================
    //  场景 3：roles='user' 容器对所有用户可见
    // ============================================================

    public function testUserRoleContainerVisible(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '会员中心', 'href' => '', 'roles' => 'user'],
            ['id' => 2, 'pid' => 1, 'title' => '文件管理', 'href' => 'tao/user.uploadfile', 'roles' => ''],
        ];
        $userNodes = ['tao/user.uploadfile'];
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, $userNodes, false);

        $this->assertCount(1, $result);
        $this->assertEquals('会员中心', $result[1]['title']);
        $this->assertCount(1, $result[1]['child']);
        $this->assertEquals('文件管理', $result[1]['child'][2]['title']);
    }

    // ============================================================
    //  场景 4：roles='user' 非容器 → 仍需节点检查
    // ============================================================

    public function testUserRoleWithHrefStillChecksNode(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '用户管理', 'href' => 'tao/admin.user', 'roles' => 'user'],
        ];
        // 用户节点，总是可见
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, ['other/node'], false);
        $this->assertCount(1, $result);

        // 节点在列表中 → 可见
        $result2 = MenuLibHelper::filterMenuTree($tree, ['tao/admin.user'], false);
        $this->assertCount(1, $result2);
    }

    // ============================================================
    //  场景 5：其他 role → 不可见
    // ============================================================

    public function testOtherRoleNotVisible(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '广告管理', 'href' => 'tao.cms/admin.ad', 'roles' => 'superAdmin'],
        ];
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, [], false);
        $this->assertCount(0, $result);
    }

    // ============================================================
    //  场景 6：空容器（href 为空 + 全部子菜单不可见）→ 过滤
    // ============================================================

    public function testEmptyContainerFiltered(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => '公共模块', 'href' => '', 'roles' => ''],
            ['id' => 2, 'pid' => 1, 'title' => '开放平台', 'href' => '', 'roles' => ''],
            ['id' => 3, 'pid' => 2, 'title' => '商户应用', 'href' => 'tao.open/admin.mch', 'roles' => 'superAdmin'],
        ];
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, ['other/node'], false);

        // 商户应用不可见（superAdmin 角色）→ 开放平台 children 空 → 开放平台被过滤
        // → 公共模块 children 空 → 公共模块被过滤
        $this->assertCount(0, $result);
    }

    // ============================================================
    //  场景 7：角色继承 — 父级 roles 传给空 roles 的子菜单
    // ============================================================

    public function testRoleInheritance(): void
    {
        $menus = [
            ['id' => 1, 'pid' => 0, 'title' => 'CMS', 'href' => '', 'roles' => ''],
            ['id' => 2, 'pid' => 1, 'title' => '广告管理', 'href' => '', 'roles' => 'superAdmin'],
            ['id' => 3, 'pid' => 2, 'title' => '广告列表', 'href' => 'cms/ad', 'roles' => ''],
        ];
        // 广告管理 roles='superAdmin' → 不可见
        // 广告列表继承 superAdmin → 也不可见
        $tree = MenuLibHelper::getSystemMenuTree(0, $menus);
        $result = MenuLibHelper::filterMenuTree($tree, ['cms/ad'], false);
        $this->assertCount(0, $result);

        // 但如果是超级管理员 → 全部可见
        $result2 = MenuLibHelper::filterMenuTree($tree, [], true);
        $this->assertCount(1, $result2);
        $this->assertEquals('CMS', $result2[1]['title']);
    }

    // ============================================================
    //  场景 8：isMenuVisible 直接测试
    // ============================================================

    public function testIsMenuVisible(): void
    {
        // roles 为空 + href 在节点列表中 → 可见
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => ''], ['demo/page'], false
        ));
        // roles 为空 + href 不在节点列表中 → 不可见
        $this->assertFalse(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => ''], ['other/node'], false
        ));
        // roles 为空 + href 为空 → 可见（容器）
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => '', 'roles' => ''], [], false
        ));
        // roles='user' 用户节点可见
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => '', 'roles' => 'user'], [], false
        ));
        // roles='user'
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => 'user'], ['demo/page'], false
        ));
        // roles='user'
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => 'user'], ['other/node'], false
        ));
        // 其他 role → 不可见
        $this->assertFalse(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => 'superAdmin'], ['demo/page'], false
        ));
        // 超级管理员 → 可见
        $this->assertTrue(MenuLibHelper::isMenuVisible(
            ['href' => 'demo/page', 'roles' => ''], [], true
        ));
    }

    public function testYihe()
    {
        $systemMenus = array(
// 系统模块
            0 => array('id' => 2, 'pid' => 0, 'title' => '系统管理', 'icon' => 'fa fa-cog', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            4 => array('id' => 4, 'pid' => 2, 'title' => '用户管理', 'icon' => 'fa fa-user-circle-o ', 'href' => 'tao/admin.user', 'type' => 2, 'roles' => '', 'params' => '',),
            5 => array('id' => 5, 'pid' => 2, 'title' => '角色管理', 'icon' => 'fa fa-users', 'href' => 'tao/admin.role', 'type' => 2, 'roles' => '', 'params' => '',),
            6 => array('id' => 3, 'pid' => 2, 'title' => '菜单管理', 'icon' => 'fa fa-tree', 'href' => 'tao/admin.menu', 'type' => 2, 'roles' => '', 'params' => '',),
            7 => array('id' => 6, 'pid' => 2, 'title' => '节点管理', 'icon' => 'fa fa-code-fork', 'href' => 'tao/admin.node', 'type' => 2, 'roles' => '', 'params' => '',),
            8 => array('id' => 7, 'pid' => 2, 'title' => '配置管理', 'icon' => 'fa fa-cogs', 'href' => 'tao/admin.config', 'type' => 2, 'roles' => '', 'params' => '',),
// 会员模块 user
            1 => array('id' => 9, 'pid' => 0, 'title' => '会员中心', 'icon' => 'fa fa-list', 'href' => '', 'type' => 0, 'roles' => 'user', 'params' => '',),
            9 => array('id' => 10, 'pid' => 9, 'title' => '文件管理', 'icon' => 'fa fa-file-text-o', 'href' => 'tao/user.uploadfile', 'type' => 2, 'roles' => '', 'params' => '',),
            10 => array('id' => 11, 'pid' => 9, 'title' => '快捷入口', 'icon' => 'layui-icon layui-icon-link', 'href' => 'tao/user.quick', 'type' => 2, 'roles' => '', 'params' => '',),
            11 => array('id' => 12, 'pid' => 9, 'title' => '日志管理', 'icon' => 'layui-icon layui-icon-date', 'href' => 'tao/user.log', 'type' => 2, 'roles' => '', 'params' => '',),
// 公共模块
            2 => array('id' => 13, 'pid' => 0, 'title' => '公共模块', 'icon' => 'fa fa-list', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            12 => array('id' => 15, 'pid' => 13, 'title' => '开放平台', 'icon' => 'fa fa-list', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            13 => array('id' => 14, 'pid' => 13, 'title' => '应用辅助', 'icon' => 'fa fa-list', 'href' => '', 'type' => 2, 'roles' => '', 'params' => '',),
            14 => array('id' => 22, 'pid' => 13, 'title' => 'CMS', 'icon' => 'fa fa-list', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            20 => array('id' => 8, 'pid' => 22, 'title' => '单页管理', 'icon' => 'fa fa-file-text-o', 'href' => 'tao.cms/admin.page', 'type' => 2, 'roles' => '', 'params' => '',),
            15 => array('id' => 16, 'pid' => 14, 'title' => '基本信息', 'icon' => 'fa fa-info', 'href' => 'tao.app/admin.info', 'type' => 2, 'roles' => '', 'params' => '',),
            16 => array('id' => 17, 'pid' => 15, 'title' => '商户应用', 'icon' => 'fa fa-calendar-check-o', 'href' => 'tao.open/admin.mch', 'type' => 2, 'roles' => '', 'params' => '',),
// 义和模块
            3 => array('id' => 29, 'pid' => 0, 'title' => '义和管理系统', 'icon' => 'fa fa-list', 'href' => '', 'type' => 2, 'roles' => '', 'params' => '',),
            26 => array('id' => 30, 'pid' => 29, 'title' => '数据导入', 'icon' => 'fa fa-table', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            27 => array('id' => 39, 'pid' => 29, 'title' => '客户中心', 'icon' => 'fa fa-users', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            29 => array('id' => 41, 'pid' => 29, 'title' => '对账管理', 'icon' => 'fa fa-money', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            28 => array('id' => 42, 'pid' => 29, 'title' => '物流调度', 'icon' => 'fa fa-truck', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            30 => array('id' => 43, 'pid' => 29, 'title' => '淤泥工地', 'icon' => 'fa fa-bar-chart', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            31 => array('id' => 45, 'pid' => 29, 'title' => '系统设置', 'icon' => 'fa fa-cogs', 'href' => '', 'type' => 0, 'roles' => '', 'params' => '',),
            32 => array('id' => 52, 'pid' => 30, 'title' => '上传出车文件', 'icon' => '', 'href' => 'yihe/dataTrip/index', 'type' => 2, 'roles' => '', 'params' => '',),
            33 => array('id' => 53, 'pid' => 30, 'title' => '上传账单文件', 'icon' => '', 'href' => 'yihe/dataPayment', 'type' => 2, 'roles' => '', 'params' => '',),
            34 => array('id' => 31, 'pid' => 39, 'title' => '客户管理', 'icon' => 'fa fa-user', 'href' => 'yihe/customer', 'type' => 2, 'roles' => '', 'params' => '',),
            35 => array('id' => 35, 'pid' => 39, 'title' => '工地管理', 'icon' => 'fa fa-map-marker', 'href' => 'yihe/site', 'type' => 2, 'roles' => '', 'params' => '',),
            36 => array('id' => 37, 'pid' => 39, 'title' => '缴费管理', 'icon' => 'fa fa-credit-card', 'href' => 'yihe/payment', 'type' => 2, 'roles' => '', 'params' => '',),
            37 => array('id' => 38, 'pid' => 39, 'title' => '其它收支', 'icon' => 'fa fa-exchange', 'href' => 'yihe/customerExtraBill', 'type' => 2, 'roles' => '', 'params' => '',),
            38 => array('id' => 56, 'pid' => 39, 'title' => '记账历史', 'icon' => '', 'href' => 'yihe/customerBalanceHistory', 'type' => 2, 'roles' => '', 'params' => '',),
            39 => array('id' => 40, 'pid' => 41, 'title' => '客户账单', 'icon' => 'fa fa-file-excel-o', 'href' => 'yihe/table/money', 'type' => 2, 'roles' => '', 'params' => '',),
            40 => array('id' => 47, 'pid' => 41, 'title' => '账款总结', 'icon' => 'fa fa-file-excel-o', 'href' => 'yihe/table/index', 'type' => 2, 'roles' => '', 'params' => '',),
            41 => array('id' => 55, 'pid' => 41, 'title' => '司机账单', 'icon' => '', 'href' => 'yihe/trip/driver', 'type' => 2, 'roles' => '', 'params' => '',),
            42 => array('id' => 33, 'pid' => 42, 'title' => '车辆管理', 'icon' => 'fa fa-car', 'href' => 'yihe/car', 'type' => 2, 'roles' => '', 'params' => '',),
            43 => array('id' => 34, 'pid' => 42, 'title' => '司机管理', 'icon' => 'fa fa-id-card-o', 'href' => 'yihe/driver', 'type' => 2, 'roles' => '', 'params' => '',),
            44 => array('id' => 36, 'pid' => 42, 'title' => '出车记录', 'icon' => 'fa fa-history', 'href' => 'yihe/trip', 'type' => 2, 'roles' => '', 'params' => '',),
            45 => array('id' => 51, 'pid' => 43, 'title' => '工地汇总', 'icon' => 'fa fa-table', 'href' => 'yihe/summary/report', 'type' => 2, 'roles' => '', 'params' => '',),
            46 => array('id' => 54, 'pid' => 43, 'title' => '历史管理', 'icon' => 'fa fa-history', 'href' => 'yihe/summary', 'type' => 2, 'roles' => '', 'params' => '',),
            47 => array('id' => 32, 'pid' => 45, 'title' => '基本配置', 'icon' => 'fa fa-sliders', 'href' => 'yihe/fee-config', 'type' => 2, 'roles' => '', 'params' => '',),
            );
        $tree = MenuLibHelper::getSystemMenuTree(0,$systemMenus);
        $userNodes = [
            "yihe/car/index", // 42
            "yihe/customerBalanceHistory/index", // 39
            "yihe/customer/index", // 39
            "yihe/customerExtraBill/index", // 39
            "yihe/driver/index", // 42
            "yihe/payment/index", // 39
            "yihe/site/index", // 39
            "yihe/summary/report", // 43
            "yihe/summary/siteTrips", // 不显示
            "yihe/trip/index", // 42
            "yihe/trip/driver", // 41
        ];
        $userTree = MenuLibHelper::filterMenuTree($tree,$userNodes);

        // 会员中心，必须全部存
        $this->assertCount(3,$userTree[9]['child']);
        // 公共模块，系统模块不应该存在
        $this->assertFalse(isset($userTree[2]));
        $this->assertFalse(isset($userTree[13]));
        // yihe 模块
        $this->assertTrue(isset($userTree[29]));
        $this->assertCount(4,$userTree[29]['child']);
        $this->assertCount(5, $userTree[29]['child'][39]['child']);
        $this->assertCount(1, $userTree[29]['child'][41]['child']);
        $this->assertCount(3, $userTree[29]['child'][42]['child']);
        $this->assertCount(1, $userTree[29]['child'][43]['child']);
    }
}
