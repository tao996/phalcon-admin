# tao 模块分析

## 概述

`tao` 模块是 Phalcon Admin 的**核心系统管理模块**，实现了基于 RBAC（基于角色的访问控制）的后台权限管理体系。该模块涵盖用户认证、角色权限、菜单管理、节点授权、系统配置、文件上传、验证码、第三方 OAuth 登录等基础能力，并通过 `A0` 子目录扩展了 CMS 内容管理和开放平台（微信/抖音小程序、支付）功能。

- **命名空间**: `App\Modules\tao`
- **模块定义**: `Module` 类继承 `\Phax\Mvc\Module`，使用 `@rbac({title:'系统管理模块'})` 注解
- **数据表前缀**: `tao_`
- **默认主题**: `layui`
- **路由前缀**: `/m/tao/`

---

## 目录结构

```
src/App/Modules/tao/
├── Module.php                    # 模块入口
├── BaseController.php            # CRUD 业务逻辑基类
├── BaseAuthController.php        # 认证控制器基类
├── BaseRbacController.php        # RBAC 权限控制器基类
├── BaseResponseController.php    # 响应格式基类
├── BaseTaoModel.php              # 模型基类（统一表前缀+时间戳）
├── Controllers/                  # 控制器
│   ├── AuthController.php        # 注册/登录/忘记密码
│   ├── CaptchaController.php     # 验证码生成
│   ├── IndexController.php       # 后台框架首页
│   ├── Oauth3Controller.php      # 第三方 OAuth 登录
│   ├── admin/                    # 管理员操作
│   │   ├── ConfigController.php  # 系统配置
│   │   ├── MenuController.php    # 菜单管理
│   │   ├── NodeController.php    # 节点管理
│   │   ├── RoleController.php    # 角色管理
│   │   ├── UserController.php    # 用户管理
│   │   └── UpgradeController.php # 更新升级
│   └── user/                     # 普通用户操作
│       ├── FileController.php       # 文件上传
│       ├── IndexController.php      # 会员中心
│       ├── LogController.php        # 日志查看
│       ├── QiniuController.php      # 七牛云上传凭证
│       ├── QuickController.php      # 快捷链接
│       └── UploadfileController.php # 文件管理
├── Models/                       # 数据模型
│   ├── SystemConfig.php          # 系统配置
│   ├── SystemLog.php             # 操作日志
│   ├── SystemMenu.php            # 系统菜单
│   ├── SystemMigration.php       # 数据迁移版本
│   ├── SystemNode.php            # 权限节点
│   ├── SystemOssFile.php         # OSS 文件记录
│   ├── SystemQuick.php           # 快捷菜单
│   ├── SystemRole.php            # 角色
│   ├── SystemRoleNode.php        # 角色-节点关联
│   ├── SystemSmsCode.php         # 验证码
│   ├── SystemUploadfile.php      # 上传文件
│   └── SystemUser.php            # 用户
├── Services/                     # 业务服务
│   ├── ConfigService.php         # 配置服务（带缓存）
│   ├── LogService.php            # 日志服务
│   ├── MenuService.php           # 菜单服务
│   ├── MigrationService.php      # 迁移服务
│   ├── NodeService.php           # 节点服务
│   ├── RoleService.php           # 角色服务
│   ├── SmsCodeService.php        # 验证码服务
│   ├── UploadfileService.php     # 上传文件服务
│   └── UserService.php           # 用户服务
├── Helper/                       # 辅助类
│   ├── Auth/                     # 认证适配器
│   │   ├── AuthRedisData.php         # Redis 认证数据
│   │   ├── LoginAppAuthAdapter.php   # APP Token 认证
│   │   ├── LoginAuthAdapter.php      # 认证适配器接口
│   │   ├── LoginCookieAuthAdapter.php # Cookie 认证
│   │   ├── LoginDemoTokenAuthAdapter.php # 测试 Token 认证
│   │   ├── LoginSessionAuthAdapter.php  # Session 认证
│   │   └── LoginUnitTestAuthAdapter.php # 单元测试认证
│   ├── Libs/                     # 工具库
│   │   ├── AppStructure.php      # 应用结构解析
│   │   ├── JwtLibHelper.php      # JWT 辅助
│   │   ├── NodeLibHelper.php     # 节点对比/树形
│   │   └── RbacAnnotation.php    # RBAC 注解解析
│   ├── Mock/                     # Mock 驱动
│   │   ├── EmailMockDriver.php   # 邮件 Mock
│   │   └── SmsMockDriver.php     # 短信 Mock
│   ├── CaptchaHelper.php         # 验证码辅助
│   ├── FileUploadHelper.php      # 文件上传辅助
│   ├── LimitRateHelper.php       # 限流辅助
│   ├── LoginAuthHelper.php       # 登录认证辅助
│   ├── LoginUserHelper.php       # 登录用户权限/菜单辅助
│   ├── MessageHelper.php         # 消息发送辅助
│   ├── MyMvcHelper.php           # 核心 MVC 辅助（DI 服务容器）
│   ├── OssUploadHelper.php       # OSS 上传辅助
│   ├── RedirectHelper.php        # 重定向辅助
│   ├── RegisterHelper.php        # 注册配置辅助
│   └── ResponseHelper.php        # 响应渲染辅助
├── A0/                           # 扩展子模块
│   ├── app/                      # 应用管理
│   │   ├── Controllers/admin/InfoController.php
│   │   ├── Models/AppFeedback.php, AppInfo.php
│   │   └── Services/AppInfoService.php
│   ├── cms/                      # CMS 内容管理
│   │   ├── Controllers/
│   │   │   ├── admin/ (Ad/Album/Article/Category/Link/Page)
│   │   │   ├── user/HelperController.php
│   │   │   └── OpenController.php
│   │   ├── Models/ (CmsAd/CmsAlbum/CmsArticle/CmsCategory/CmsContent/CmsLink/CmsPage)
│   │   └── views/
│   └── open/                     # 开放平台
│       ├── Controllers/
│       │   ├── admin/ (App/Config/Mch/Order)
│       │   ├── demo/PayController.php
│       │   ├── weixin/ (Auth/Mini/Official)
│       │   ├── AuthController.php
│       │   └── UserController.php
│       ├── Models/ (OpenApp/OpenConfig/OpenMch/OpenOrder/OpenUserOpenid/OpenUserUnionid/OpenUserWork)
│       ├── Service/ (OpenApp/OpenConfig/OpenMch/OpenOrder/OpenUser)
│       ├── Logic/WepayOrderLogic.php
│       ├── ExtendControllers/ (UserOrder/WepayOrder)
│       ├── Helper/ (Application/MiniApp/MyOpenMvc/OpenUrl/Tiktok/Wechat/Wepay + wepay/ + Libs/)
│       ├── Data/Config.php
│       ├── sdk/easytiktok/
│       ├── BaseOpenController.php
│       └── BaseOpenMiniController.php
├── sdk/                          # 第三方 SDK
│   ├── aliyun/                   # 阿里云 OSS/SMS
│   ├── captcha/                  # 验证码生成
│   ├── phaxui/                   # 前端 UI 组件（Layui 封装）
│   ├── qiniu/                    # 七牛云
│   ├── tencent/                  # 腾讯云
│   ├── easywechat.phar           # EasyWeChat
│   ├── hybridauth.phar           # HybridAuth（OAuth）
│   ├── qrcode.phar               # 二维码
│   ├── EmailDriverInterface.php  # 邮件驱动接口
│   ├── OssDriverInterface.php    # OSS 驱动接口
│   ├── SmsDriverInterface.php    # 短信驱动接口
│   ├── RedisCache.php            # Redis 缓存
│   └── SdkHelper.php             # SDK 初始化辅助
├── Config/
│   ├── Config.php                # 模块常量（表前缀/验证码配置/欢迎页）
│   └── Data.php                  # 数据常量（首页PID/平台类型/访问级别）
├── Common/
│   ├── Actions/AccountMigrateAction.php  # 账号迁移
│   ├── BaseProjectController.php         # 项目控制器基类
│   ├── BaseProjectModel.php              # 项目模型基类
│   └── common.php                        # 全局路径常量
├── data/
│   ├── migration/1.0.0/          # 数据库迁移脚本（26表 .dat + .php）
│   └── sql/                      # SQL 初始化脚本
├── views/
│   └── layui/                    # Layui 主题视图（82 js/42 phtml/29 css）
└── tests/
    ├── Helper/                   # 测试辅助
    │   ├── MyTestTaoControllerHelper.php
    │   └── MyTestTaoHttpHelper.php
    └── PHPUnit/                  # PHPUnit 测试（30个测试文件）
```

---

## 控制器继承体系

```
\Phax\Mvc\Controller
  └── BaseAuthController          # 认证层：登录状态检查
        └── BaseResponseController  # 响应层：JSON/视图格式化、分页、主题
              └── BaseRbacController  # 权限层：RBAC 节点访问控制
                    └── BaseController  # 业务层：CRUD 操作封装
                          └── 具体业务控制器
```

### 各层职责

| 类 | 职责 |
|---|---|
| `BaseAuthController` | 初始化 `MyMvcHelper`，提供 `isLogin()` / `tryGetLoginAuth()` |
| `BaseResponseController` | 定义 `success()`/`error()`/`successPagination()` 响应格式，JSON Body 请求处理，视图主题，分页，Layui 搜索重置 |
| `BaseRbacController` | RBAC 权限校验：`openActions`/`superAdminActions`/`userActions`/`otherActionRoles` 分级访问控制，登录检查，节点权限校验，`mustPostMethod()` |
| `BaseController` | 封装 `indexAction`/`addAction`/`editAction`/`deleteAction`/`modifyAction` 五种 CRUD 操作，提供 `allowModifyFields`、`saveWhiteList`、`checkModelActionAccess()` 等扩展点 |

---

## RBAC 权限模型

```
SystemUser ──(role_ids)──→ SystemRole ──(多对多)──→ SystemRoleNode ──→ SystemNode
```

### 权限层级

1. **超级管理员**（`superAdminIds` 配置）：拥有所有权限
2. **角色管理员**（`superAdminActions`）：指定 action 仅超级管理员可访问
3. **普通用户**（`userActions`）：指定 action 已登录用户可访问
4. **角色绑定**（`otherActionRoles`）：指定角色可访问
5. **节点检查**：通过 `SystemRoleNode` 关联表查询用户角色绑定的节点

### 节点类型

| 常量 | 值 | 含义 |
|---|---|---|
| `KIND_PROJECT` | 1 | 项目节点 |
| `KIND_MODULE` | 2 | 模块节点 |
| `TYPE_MODULE` | 1 | 模块级 |
| `TYPE_CONTROLLER` | 2 | 控制器级 |
| `TYPE_ACTION` | 3 | 操作级 |

### 注解驱动

通过 `@rbac({title:'...', close:1})` 注解自动扫描注册节点，`RbacAnnotation` 负责解析控制器/方法的注解并生成节点列表。

---

## 认证体系

### 认证适配器

| 适配器 | 场景 |
|---|---|
| `LoginCookieAuthAdapter` | Web 端 Cookie 认证（默认） |
| `LoginSessionAuthAdapter` | Session 认证 |
| `LoginAppAuthAdapter` | APP/小程序 Token 认证 |
| `LoginDemoTokenAuthAdapter` | 单元测试 Token 认证 |
| `LoginUnitTestAuthAdapter` | 单元测试认证 |

### 认证流程

1. `LoginAuthHelper::setAuthAdapter()` 自动检测请求类型并选择适配器
2. `LoginAuthHelper::login()` 调用适配器获取用户信息
3. `LoginUserHelper` 管理用户权限和菜单

### 登录方式

- 账号密码登录（邮箱/手机号 + 密码 + 图形验证码）
- 验证码登录（手机/邮箱 + 短信/邮件验证码）
- 第三方 OAuth 登录（Google，通过 Hybridauth）
- 小程序登录（微信/抖音）

---

## 核心服务

### UserService

用户全生命周期管理：注册、登录（密码/验证码）、密码修改/重置、账号修改（手机/邮箱，30天限制）、唯一性校验、OAuth 用户创建。

### ConfigService

系统配置管理：以 `gname.name = value` 方式存储，通过 Redis 缓存加速，支持 `forceCache()` 强制刷新、`uploadConfig()` 合并文件配置。

### SmsCodeService

验证码服务：支持注册/登录/修改账号/忘记密码四种场景，每天限制发送次数，验证码 15 分钟有效，最多允许 3 次错误。

### NodeService

节点服务：节点对比（新增/更新/删除）、节点树生成、角色授权节点查询。

### MenuService

菜单服务：菜单链接生成（自动添加 `/m/` 或 `/p/` 前缀）、首页 ID 获取。

### MigrationService

数据迁移服务：版本号唯一性检查，事务中执行升级脚本。

---

## 数据模型

### 模型继承

所有模型继承 `BaseTaoModel`，统一使用 `tao_` 表前缀，自动包含 `id`/`created_at`/`updated_at`/`deleted_at` 字段。`SoftDelete` trait 提供软删除支持。

### 模型一览

| 模型 | 表 | 说明 |
|---|---|---|
| `SystemUser` | tao_system_user | 用户（角色/密码/邮箱/手机/绑定） |
| `SystemRole` | tao_system_role | 角色（名称/标题/排序/状态） |
| `SystemRoleNode` | tao_system_role_node | 角色-节点关联 |
| `SystemNode` | tao_system_node | 权限节点（模块/控制器/操作三级） |
| `SystemMenu` | tao_system_menu | 菜单（树形，支持模块/项目链接） |
| `SystemConfig` | tao_system_config | 配置（分组名.名称=值） |
| `SystemLog` | tao_system_log | 操作日志 |
| `SystemSmsCode` | tao_system_sms_code | 验证码（短信/邮件） |
| `SystemUploadfile` | tao_system_uploadfile | 上传文件 |
| `SystemOssFile` | tao_system_oss_file | OSS 文件记录 |
| `SystemQuick` | tao_system_quick | 快捷菜单 |
| `SystemMigration` | tao_system_migration | 数据迁移版本 |

---

## A0 子模块

### app — 应用管理

- 应用信息管理（`AppInfo`）
- 应用反馈管理（`AppFeedback`）

### cms — 内容管理

- 广告管理（`CmsAd`）
- 相册管理（`CmsAlbum`）
- 图文/文章管理（`CmsArticle`/`CmsContent`）
- 分类管理（`CmsCategory`）
- 链接管理（`CmsLink`）
- 单页管理（`CmsPage`）

### open — 开放平台

- 第三方应用管理（`OpenApp`/`OpenConfig`）
- 商户管理（`OpenMch`）
- 订单管理（`OpenOrder`）
- 微信登录/小程序/公众号（`weixin/` 控制器）
- 抖音小程序（Tiktok SDK）
- 微信支付（`WepayHelper`/`WepayOrderLogic`）
- 用户 OpenID/UnionID 绑定

---

## SDK 集成

| SDK | 用途 |
|---|---|
| `hybridauth.phar` | 第三方 OAuth 登录（Google 等） |
| `easywechat.phar` | 微信 SDK（小程序/公众号/支付） |
| `qrcode.phar` | 二维码生成 |
| `aliyun/` | 阿里云 OSS/SMS 驱动 |
| `qiniu/` | 七牛云 OSS 驱动 |
| `tencent/` | 腾讯云 COS 驱动 |
| `captcha/` | 图形验证码（TTF 字体） |
| `phaxui/` | Layui UI 组件封装（表格/表单/HTML） |

所有驱动通过接口抽象：`OssDriverInterface`、`SmsDriverInterface`、`EmailDriverInterface`。

---

## 前端视图

- 使用 **Layui** 作为 UI 框架
- 视图目录：`views/layui/`
- `HtmlAssets` 管理 CDN 资源加载
- `LayuiData` 提供 `treeTable()`、`bool2Int()` 等数据转换
- `LayuiForm` / `LayuiHtml` 提供表单和 HTML 组件封装
- 支持自动引入同名 JS 文件（如 `add.phtml` 对应 `add.js`）

---

## 数据迁移

- 迁移脚本位于 `data/migration/1.0.0/`，包含 26 张表的 `.dat`（数据）和 `.php`（迁移定义）文件
- `MigrationService::upgrade()` 提供事务安全的版本升级
- `SystemMigration` 表记录已执行的版本号

---

## 测试

- 测试文件位于 `tests/PHPUnit/`（30 个测试文件）
- 测试辅助：`MyTestTaoControllerHelper`、`MyTestTaoHttpHelper`
- Mock 驱动：`EmailMockDriver`、`SmsMockDriver`
- 专用认证适配器：`LoginDemoTokenAuthAdapter`、`LoginUnitTestAuthAdapter`

---

## 关键设计模式

1. **控制器模板方法模式**：`BaseController` 定义 CRUD 骨架，子类通过 `afterInitialize()`、`beforeModelSave()`、`afterModelChange()` 等钩子扩展
2. **服务定位器模式**：`MyMvcHelper` 作为 DI 容器门面，注入并代理所有服务访问
3. **适配器模式**：认证体系通过 `LoginAuthAdapter` 接口支持多种认证方式
4. **注解驱动**：RBAC 节点通过 `@rbac` 注解自动扫描注册
5. **策略模式**：OSS/SMS/Email 驱动通过接口抽象，支持多厂商切换
