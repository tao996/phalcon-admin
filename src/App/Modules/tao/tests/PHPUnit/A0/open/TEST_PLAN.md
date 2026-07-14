# 测试计划：`A0/open/Controllers` 开放平台 / 微信第三方控制器

> 创建时间：2026-01-29
> 对应目录：`src/App/Modules/tao/A0/open/Controllers/`

---

## 一、待测控制器清单

| 分组 | 文件 | 基类 | 角色 | 外部依赖强度 |
|------|------|------|------|-------------|
| 公开 API | `AuthController` | `BaseOpenMiniController` | PUID 登录、账号密码登录（含算术验证码） | 中（DB + Session） |
| 公开 API | `UserController` | `BaseOpenMiniController` | 用户资料查询/修改、退出登录 | 中（DB + 登录态） |
| 微信 | `weixin/AuthController` | `BaseController` | 公众号 OAuth 授权跳转 & 回调 | **高**（EasyWeChat OAuth） |
| 微信 | `weixin/MiniController` | `BaseOpenMiniController` | 小程序 code2session、生成小程序码 | **高**（EasyWeChat Mini） |
| 微信 | `weixin/OfficialController` | `BaseOpenMiniController` | 公众号消息/事件推送处理 | **高**（EasyWeChat Server） |
| 管理后台 | `admin/AppController` | `BaseController` | OpenApp 应用 CRUD、证书上传 | 中（DB + RBAC + 文件） |
| 管理后台 | `admin/ConfigController` | `BaseController` | OpenConfig 配置管理 | 中（DB） |
| 管理后台 | `admin/MchController` | `BaseController` | 商户管理、证书上传 | 中（DB + RBAC + 文件） |
| 管理后台 | `admin/OrderController` | `BaseController` | 订单管理列表（仅 index） | 低（DB） |
| Demo | `demo/PayController` | `BaseController` | JSAPI 支付下单、通知、退款通知 | **高**（EasyWeChat Pay） |

---

## 二、现有测试基础设施

项目已有两套可复用的测试模式：

### 2.1 HTTP 端到端（`MyTestHttpHelper`）

- **原理**：通过 cURL 向 `TEST_ORIGIN`（Docker nginx）发起真实 HTTP 请求
- **用例位置**：`src/tests/Unit/app/Modules/demo/ControllersTest.php`
- **适用场景**：全链路冒烟验证
- **注意**：`TEST_SKIP_HTTP = true` 时会跳过，需显式配置才启用

### 2.2 控制器直调（`MyTestControllerHelper`）

- **原理**：直接实例化 Controller，注入 Mock 的 request/response/session
- **用例位置**：`src/tests/Unit/app/Projects/demo/ControllersTest.php`
- **适用场景**：单元/集成测试，不依赖网络
- **特点**：可控制请求参数、验证返回值，不受 `TEST_SKIP_HTTP` 影响

### 2.3 Mock 能力

- PHPUnit 11.2 + Mockery 1.6 可用
- 测试 bootstrap（`src/tests/bootstrap.php`）已初始化 DI、DB、Redis、缓存

---

## 三、分层测试策略

> 核心原则：**不应以单一策略覆盖所有控制器，应按外部依赖强度分三层。**

### 第 1 层：低外部依赖 — 控制器直调

**适用对象**：`admin/OrderController`

- 方法：`MyTestControllerHelper` + mock request/session
- 验证：`indexAction()` 在 API 模式下返回正确分页 JSON
- 不需 mock：无外部 API 调用，无文件上传
- 估算工作量：1 个测试类，约 1-2 小时

### 第 2 层：中外部依赖 — 控制器直调 + Mockery

**适用对象**：`AuthController`、`UserController`、`admin/AppController`、`admin/ConfigController`、`admin/MchController`

- 方法：`MyTestControllerHelper` + Mockery mock 服务层
- 关键 mock 点：
  - `AuthController`：mock session（验证码两步交互）、mock `UserService::loginWithPassword()` 切断 DB
  - `UserController`：mock `loginUser()` 返回固定用户、mock `OpenUserOpenid` 查询
  - admin 控制器：测试焦点在 `beforeModelAssign()` / `certAction()` 等自定义逻辑，CRUD 标准行为由框架保证
- 验证码测试要点：
  - 第一次调用（无 captcha）→ 获取 `rule`
  - 计算答案 → 第二次调用（带 captcha）
  - 分别验证：正确、过期、错误超限三种场景
- 估算工作量：每控制器 1 个测试类，约 4-6 小时

### 第 3 层：高外部依赖 — Guzzle MockHandler 中间件（推荐）

**适用对象**：`weixin/AuthController`、`weixin/MiniController`、`weixin/OfficialController`、`demo/PayController`

#### 3.1 方案概述

```
生产： 业务代码 → EasyWeChat SDK → Guzzle → 微信 API
测试： 业务代码 → EasyWeChat SDK → [MockHandler] → 返回 fixture 数据
                                             ↑
                                    只在测试环境激活
```

EasyWeChat 6.x 底层基于 Guzzle HTTP Client，Guzzle 的 `HandlerStack` 原生支持插入中间件。在测试环境中向 HandlerStack 推入一个 MockHandler，匹配 URL 模式后直接返回预设的 `Response`。

#### 3.2 方案优势（与 Mockery 链式 mock 对比）

| 维度 | Guzzle MockHandler | Mockery 链式 mock |
|------|-------------------|-------------------|
| 侵入性 | 零，不改业务代码 | 需重构或理解深度调用链 |
| mock 链路深度 | 1 层（拦截 HTTP 请求） | 4~5 层（`helper→getOfficial→getOAuth→redirect`） |
| 覆盖真实序列化 | ✅ EasyWeChat 真的拼 JSON 发送 | ❌ 跳过序列化，直接返回 mock 值 |
| 对回调式调用的支持 | ✅ 良好（如 `$server->with(function(){...})` 仍然触发） | ❌ 差 |
| 维护成本 | 低（Guzzle 接口稳定，EasyWeChat 升级不影响） | 高 |
| 错误注入 | 容易（返回不同 HTTP 状态码 / 错误 JSON） | 也可做到但代码更复杂 |

#### 3.3 Fixture 数据组织

```
src/App/Modules/tao/tests/PHPUnit/A0/open/fixtures/
  weixin/
    code2session_success.json           ← { session_key, openid, unionid }
    code2session_invalid_code.json      ← { errcode: 40029, errmsg: "invalid code" }
    getwxacodeunlimit_success.bin       ← (图片二进制数据)
    oauth_redirect.html                 ← (微信 OAuth 页面)
    oauth_userinfo.json                 ← (OAuth 用户信息)
    official_subscribe.xml              ← (关注事件 XML)
    official_text_message.xml           ← (文本消息 XML)
    official_echostr.txt               ← (echostr 验证明文)
  pay/
    prepay_success.json                 ← (预支付参数)
    notify_success.xml                  ← (支付成功回调 XML)
    refund_notify.xml                   ← (退款回调 XML)
```

#### 3.4 不适用的场景（需单独处理）

1. **`weixin/OfficialController` 的 echostr 验签**：不经过 EasyWeChat，是微信服务器直接 GET 请求 → 需在控制器直调模式下单独测试
2. **`demo/PayController` 的 notify/refundNotify 回调**：微信 POST XML 到接口 → 可在直调模式下 mock request body 为回调 XML

#### 3.5 估算工作量

- 搭建 MockHandler 基础设施 + 编写 fixture：约 2-3 小时
- 四个控制器各 1 个测试类：约 6-8 小时

---

## 四、关键技术难点与应对

| 难点 | 表现 | 应对方案 |
|------|------|---------|
| 验证码两步交互 | `AuthController` 用 session 分两步验证 | `MyTestControllerHelper` 两次调用 action：先取 rule → 计算答案 → 再带 captcha |
| Phalcon Model 静态查询 | 控制器直接调 `SystemUser::queryBuilder()` | 抽取到 Repository/Service 层再 mock；或使用内存 SQLite |
| 登录态依赖 | `loginUser()` 需要已认证会话 | 注入 mock session 预设用户信息 |
| RBAC 注解解析 | `#[RBAC]` 在 `initialize()` 中处理 | 测试中需调用 `$controller->initialize()` |
| LocationException | OAuth 跳转通过 `throw new LocationException($url)` | `try-catch` 捕获并验证 URL 参数 |
| 文件上传 | `certAction` 涉及 `getUploadedFiles()` | Mock request 需支持文件上传模拟 |
| Phalcon C 扩展 | 某些环境下不可用 | 项目已有 `php artisan phalcon` 切换机制 |

---

## 五、实施优先级

```
优先级 1 ─── admin/OrderController          （低难度，建测试模式样板）
优先级 2 ─── AuthController + UserController（核心业务逻辑，验证码复杂）
优先级 3 ─── admin/AppController + ConfigController + MchController（CRUD + 证书管理）
优先级 4 ─── weixin/MiniController            （对外 API，Guzzle MockHandler 首例）
优先级 5 ─── weixin/AuthController + OfficialController（OAuth 流程）
优先级 6 ─── demo/PayController                （依赖前置）
```

---

## 六、不需要测试的内容

- `admin/OrderController` 的 CRUD 增删改：继承自 `BaseController` 的标准实现，框架已有覆盖
- admin 控制器的 `indexAction()` + RBAC 标准行为：`BaseController` 已提供测试
- EasyWeChat SDK 自身功能：非本项目代码

---

## 七、测试目录结构（目标）

```
src/App/Modules/tao/tests/PHPUnit/A0/open/
├── TEST_PLAN.md                              ← 本文件
├── fixtures/
│   └── weixin/
│       ├── code2session_success.json
│       ├── code2session_invalid_code.json
│       ├── getwxacodeunlimit_success.bin
│       ├── oauth_redirect.html
│       ├── oauth_userinfo.json
│       ├── official_subscribe.xml
│       ├── official_text_message.xml
│       └── official_echostr.txt
│   └── pay/
│       ├── prepay_success.json
│       ├── notify_success.xml
│       └── refund_notify.xml
├── GuzzleMockHandler.php                     ← MockHandler 基础设施
├── Controllers/
│   ├── AuthControllerTest.php
│   ├── UserControllerTest.php
│   ├── weixin/
│   │   ├── AuthControllerTest.php
│   │   ├── MiniControllerTest.php
│   │   └── OfficialControllerTest.php
│   ├── admin/
│   │   ├── AppControllerTest.php
│   │   ├── ConfigControllerTest.php
│   │   ├── MchControllerTest.php
│   │   └── OrderControllerTest.php
│   └── demo/
│       └── PayControllerTest.php
```

---

## 八、phpunit.xml 注册

在 `src/phpunit.example.xml` 中新增 testsuite：

```xml
<testsuite name="tao-open">
    <directory>App/Modules/tao/tests/PHPUnit/A0/open/Controllers</directory>
</testsuite>
```
