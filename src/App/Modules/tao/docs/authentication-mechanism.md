# phalcon-admin `tao` 模块用户认证与授权机制

> 本文以当前 `tao` 模块代码为准。用户所说的 `AuthConCtroller.php` 在仓库中的实际文件名是 [`AuthController.php`](../Controllers/AuthController.php)。
>
> 本文重点分析 [`AuthController.php`](../Controllers/AuthController.php)、[`Oauth3Controller.php`](../Controllers/Oauth3Controller.php) 以及 `views/layui/auth/` 下的认证页面；同时引用登录适配器、用户服务和验证码服务来说明认证结果如何进入后续的 RBAC 授权流程。

## 1. 总体结论

`tao` 模块把“认证”和“授权”分成两层：

1. **身份认证（Authentication）**：通过账号密码、邮箱/手机号验证码、邮件重置密码或 Google OAuth 证明用户身份。
2. **业务授权（Authorization）**：认证成功后，由 `BaseRbacController` 根据用户的角色、角色节点和超级管理员配置决定能否访问后台业务页面。认证控制器本身不负责给用户分配角色或业务权限。

当前 Web 页面支持的主要身份来源如下：

| 方式 | 入口 | 主要凭证 |
|---|---|---|
| 账号密码 | `AuthController::indexAction` | 账号、密码、图片验证码 |
| 邮箱/手机号验证码 | `signinCodeAction` → `signinAction` | 图片验证码、短信或邮件验证码 |
| 账号注册 | `signupCodeAction` → `signupAction` | 注册验证码、密码 |
| 忘记密码 | `forgotAction` → `passwordAction` | 图片验证码、带签名的邮件链接 |
| Google OAuth | `Oauth3Controller::indexAction` | HybridAuth 授权回调和用户 Profile |

在普通浏览器请求中，认证成功后的核心状态保存在 **Session 的 `user_id`** 中，而不是由认证页面自行维护一个前端登录状态。后续请求由登录适配器重新加载 `SystemUser`，再交给 `LoginUserHelper` 和 RBAC 基类做权限判断。

## 2. 路由、控制器和视图的对应关系

模块路由遵循 `/m/{module}/{controller}/{action}`，认证页面由 `AppService::urlModule()` 生成 URL。

| 控制器 Action | 视图 | 用途 | 请求方式 |
|---|---|---|---|
| `AuthController::indexAction` | [`index.phtml`](../views/layui/auth/index.phtml) | 密码登录 | GET 渲染；POST 登录 |
| `AuthController::signinAction` | [`signin.phtml`](../views/layui/auth/signin.phtml) | 验证码登录 | GET 渲染；POST 校验验证码并登录 |
| `AuthController::signinCodeAction` | `signin.phtml` 内的弹窗 | 发送登录验证码 | 仅 POST |
| `AuthController::signupAction` | [`signup.phtml`](../views/layui/auth/signup.phtml) | 提交注册 | GET 渲染；POST 注册 |
| `AuthController::signupCodeAction` | `signup.phtml` 内的弹窗 | 发送注册验证码 | 仅 POST |
| `AuthController::forgotAction` | [`forgot.phtml`](../views/layui/auth/forgot.phtml) | 发送重置密码邮件 | GET 渲染；POST 发送邮件 |
| `AuthController::passwordAction` | [`password.phtml`](../views/layui/auth/password.phtml) | 通过邮件链接设置新密码 | GET 展示；POST 提交 |
| `Oauth3Controller::indexAction` | 无独立 phtml | Google OAuth 发起、回调和登录 | GET |

`quickLogin.phtml` 被密码登录、验证码登录、注册和忘记密码页面共同 include；只有 `TaoAppService::registerHelper()->supportGoogle()` 为真时，才显示 Google 登录链接。

## 3. 访问控制边界

### 3.1 认证控制器是公开入口

`AuthController` 和 `Oauth3Controller` 都声明了：

```php
protected array|string $openActions = '*';
```

因此认证 Action 不需要先登录，也不会要求后台角色节点授权。`Oauth3Controller` 另外声明：

```php
public bool $disableUpdateActions = true;
```

禁止通过这个控制器使用继承来的 `add/edit/modify/delete` 操作。认证入口只负责建立登录状态，不承担后台 CRUD 权限。

### 3.2 已登录用户不能重新进入认证流程

`AuthController::afterInitialize()` 在控制器初始化时调用 `isLogin()`；如果已经登录，就执行 `RedirectUtil::read()` 跳转，不继续显示登录、注册或找回密码页面。

`Oauth3Controller::indexAction()` 也显式拒绝已登录用户发起第三方授权，避免在已有后台会话的情况下重新绑定或覆盖身份。

### 3.3 认证成功后才进入 RBAC

认证成功后，`saveUser()` 把 `SystemUser` 写入当前认证适配器。后续访问受保护控制器时：

1. `BaseRbacController` 通过 `tryGetLoginAuth()` 加载当前用户；
2. `LoginUserHelper` 根据 `SystemUser.role_ids` 读取角色对应的节点；
3. `BaseRbacController` 再按 `openActions`、`userActions`、超级管理员配置、角色配置和节点权限决定是否放行。

所以，OAuth 或验证码登录成功并不等于拥有后台全部权限；新用户的角色仍由用户模型和后台角色配置决定。

## 4. 登录状态如何建立和读取

### 4.1 普通 Web 请求使用 Session 适配器

`LoginAuthHelper::setAuthAdapter()` 会根据请求特征选择适配器。普通浏览器页面没有 App Token 或测试 Header 时，默认使用 `LoginSessionAuthAdapter`。

登录成功时：

```php
$this->getLoginAdapter()->saveUser($user);
```

`LoginSessionAuthAdapter::saveUser()` 的行为是：

- 将用户 ID 写入 Session 的 `user_id`；
- 返回一个形如 `userId:web:timestamp` 的登录标识；
- 浏览器后续依靠 Session Cookie 保持登录状态。

`AuthController` 返回的 `data` 是适配器的返回值，前端密码登录页面并不依赖它来手工保存凭证。后续请求由 Session 适配器根据 `user_id` 重新读取 `SystemUser`。

### 4.2 适配器是可替换的认证边界

同一套控制器也可以在其他请求场景使用其它适配器，例如 App Token 或测试 Token。`Oauth3Controller` 通过 `getLoginAdapter()`、`AuthController` 通过 `TaoAppService::loginAuthHelper()->getAdapter()` 获取适配器，因此二者最终建立的是哪种登录态，取决于当前请求选中的适配器，而不是由 OAuth 或密码 action 自己实现。

## 5. 各种认证流程

### 5.1 账号密码登录

#### 前端

[`index.phtml`](../views/layui/auth/index.phtml) 收集：

- `account`：邮箱或手机号；
- `password`；
- `captcha`：由 `$form->jsCaptcha()` 获取的图片验证码答案。

提交时通过 `admin.ajax.post()` 请求 `tao/auth/index`，成功后调用 [`auth.js`](../views/layui/auth/auth.js) 中的 `auth.afterLogin()`，默认延迟约一秒跳转到 `/`。

#### 后端顺序

`AuthController::indexAction()` 的 POST 分支按以下顺序执行：

1. 用 `MyAssert::mustHasSet()` 检查 `account`、`password`、`captcha`；
2. 调用 `TaoAppService::captchaHelper()->compare()` 比较图片验证码；
3. 在演示模式且输入匹配 `app.demo.admin` 配置时，加载第一个 `SystemUser`；
4. 非演示快捷路径调用 `UserService::loginWithPassword()`；
5. `UserService` 按邮箱或手机号查找已验证账号，使用安全组件校验密码，并拒绝被禁止登录的账号；
6. 调用 `loginAuthHelper()->getAdapter()->saveUser($user)` 建立 Session 登录态；
7. 返回 `code=0` 的成功响应。

演示快捷路径仍然需要先通过图片验证码；它只是在演示环境绕过正常的密码校验，不应被视为生产认证方式。

### 5.2 邮箱/手机号验证码登录

#### 发送验证码

`signinCodeAction()` 强制要求 POST，并接收 `account` 和图片验证码 `captcha`：

1. `SmsCodeService::mustReceiver()` 判断账号是邮箱还是手机号；
2. 比较图片验证码；
3. `UserService::mustCanLogin()` 检查账号是否存在且对应邮箱/手机号已经验证；
4. `SmsCodeService::sendLoginCode()` 发送短信或邮件验证码。

[`signin.phtml`](../views/layui/auth/signin.phtml) 通过弹窗输入图片验证码，成功后开始 60 秒前端倒计时。这个倒计时只是交互限制，真正的重复发送、有效性和次数限制由 `SmsCodeService` 及验证码记录负责。

#### 提交验证码

`signinAction()` 的 POST 分支：

1. 检查 `account` 和 `vercode`；
2. 校验验证码类型、有效期、错误次数和值；
3. 按账号类型查询 `SystemUser`，并要求 `email_valid=1` 或 `phone_valid=1`；
4. 保存当前用户到认证适配器；
5. 销毁图片验证码；
6. 返回登录成功响应。

该流程不要求用户提交密码，因此属于独立的免密登录凭证。

### 5.3 账号注册

注册分为“发送注册验证码”和“提交注册”两步。

`signupCodeAction()` 要求 POST 和 `account`、`captcha`：

- 校验账号格式和图片验证码；
- 检查账号是否已注册且已验证；
- 调用 `SmsCodeService::sendRegisterCode()` 发送注册验证码；
- 代码中保留了 IP 检查 TODO。

`signupAction()` 的 POST 分支：

1. 检查 `account`、`vercode`、`password`；
2. 调用 `UserService::mustAccountString()`、`mustCanRegister()`；
3. 校验注册验证码；
4. 在事务中创建 `SystemUser`；
5. `newPassword()` 校验密码至少 8 位、同时包含字母和数字，并使用安全组件哈希；
6. `newAccount()` 将邮箱或手机号写入用户，并设置对应的 `*_valid=1`；
7. 保存用户后将验证码标记为已使用。

用户注册成功后只返回“账号注册成功”，不会在注册 action 中自动建立登录态。`signup.phtml` 通过提示框引导用户回到登录页。

前端确认密码和同意用户协议是 Layui 的客户端校验：确认密码只在前端比较，`agreement` 也没有提交给后端。真正的密码规则由后端 `UserService::mustPassword()` 再次执行；客户端校验不能替代服务端授权或业务校验。

### 5.5 App Token 登录

App/Open 认证与上面的 Web 表单认证是两条不同链路。当前后端已有的 App 账号密码入口是：

```text
POST /api/m/tao.open/auth/login
```

客户端使用 JSON body；App Adapter 的自动选择要求显式传入 `data=jsonbody` 或 `kind=app`，后续带 Authorization 的请求也会进入 App 选择。仅有 `Content-Type: application/json` 不会覆盖 Web Session。

`LoginAuthHelper` 的自动选择顺序现在是：

1. 显式传入的 Adapter；
2. 测试环境的 `test-token`；
3. 明确标记的 App 请求，按 `app.app_auth_adapter` 选择 `db` 或 `redis`；
4. Web Session。

App Token 默认使用 **1 年滑动有效期**：

- 有效请求达到续期阈值后重新计算有效期；
- 连续 1 年没有有效活动才失效；
- 主动调用 logout 会立即删除当前 Token；
- `app.auth_max_login_records` 默认为 `0`，不自动挤出旧设备；需要限制设备数时再配置为正数；
- Token 缺失、过期或签名错误使用 401，RBAC 权限不足仍使用 403。

App Token 的第三段是包含时间和随机 nonce 的不透明值，secret 使用密码学安全随机数生成。Redis 方案通过 TTL 续期，DB 方案通过 `updated_at` 判断和续期。

新客户端默认发送 v2 请求头：

```json
{"v":2,"alg":"hmac-sha256","token":"...","t":1700000000,"nonce":"...","sign":"..."}
```

服务端校验时间窗口、HMAC-SHA256 和一次性 nonce；没有 `v`/`alg` 的旧 MD5 请求仍兼容。完成旧客户端迁移后，将 `app.auth_allow_legacy_signature` 设为 `false` 关闭 v1。v2 防重放依赖 Redis，服务不可用时拒绝请求。


#### 发送邮件

`forgotAction()` 要求 `account` 和图片验证码，验证码通过后调用 `SmsCodeService::sendForgotPasswordEmail()`。当前视图和服务层将它设计为邮箱重置流程：服务层会验证邮箱格式、查找已验证邮箱，并生成重置记录和邮件链接。

#### 校验链接并设置密码

`passwordAction()` 的 GET 和 POST 都会先从 Query 中读取：

- `type`：必须为 `forgot`；
- `sign`：邮件链接签名；
- `id`：重置记录 ID。

`SmsCodeService::checkForgotPasswordEmail()` 校验记录存在、未过期、签名正确且绑定了用户。签名由服务端生成，核心形式是重置记录验证码、用户 ID 和应用密钥的组合摘要；应用密钥本身不应写入日志或文档。

POST 时再检查新密码，读取 `code.user_id` 对应的用户，调用 `newPassword()` 保存新密码，并将重置记录标记为已使用。成功后页面返回密码登录入口。

因此邮件链接是一个有时效、可校验、只能成功消费一次的密码重置凭证；重置密码本身不会自动登录用户。

## 6. Google OAuth 授权登录

### 6.1 发起授权

[`quickLogin.phtml`](../views/layui/auth/quickLogin.phtml) 在 Google 登录开关开启时生成：

```text
/m/tao/oauth3?d=google
```

`Oauth3Controller::afterInitialize()` 通过 `SdkHelper::hybridauth()` 加载 HybridAuth。`indexAction()` 要求 Query 中存在 `d=driver`，并拒绝已登录用户。

初次授权请求如果没有 `state`，可以携带 `_redirect`：

1. 控制器对重定向地址执行同源/相对路径检查；
2. 通过 `RedirectUtil::save()` 保存到 Session；
3. 配置 HybridAuth 的回调地址为同一个 `tao/oauth3?d=...`；
4. 当前实现的 Provider 列表只有 `Google`，并且必须处于 enabled 状态。

### 6.2 回调、账号匹配和建立登录态

Google 回调携带 `state`、授权码等参数后，控制器再次进入 `indexAction()`，但不会再次保存 `_redirect`。HybridAuth 负责授权码交换和 `state` 校验，控制器随后：

1. `getUserProfile()` 取得第三方 Profile；
2. `disconnect()` 断开第三方适配器；
3. 将 Profile 交给 `UserService::addUserProfile()`；
4. 保存或创建本地 `SystemUser`；
5. 调用当前登录适配器的 `saveUser()`；
6. `RedirectUtil::read()` 跳转回保存的目标地址，默认是后台首页。

`UserService::addUserProfile()` 的本地账号匹配顺序是：

1. 优先查找 Profile 中已验证的邮箱；
2. 再查找已验证的手机号；
3. 两者都找不到时创建新用户，写入邮箱/手机号、昵称和头像，并记录 `gmail` 平台绑定。

OAuth 路径不提交本地密码；它依赖第三方 Profile 完成身份建立。当前代码没有在 OAuth action 中显式分配后台角色，因此新用户的业务权限仍取决于后续角色配置。

## 7. 认证凭证和状态的生命周期

| 阶段 | 存储或校验位置 | 生命周期 |
|---|---|---|
| 图片验证码 | `TaoAppService::captchaHelper()` 管理的服务端状态 | 登录/注册发码/找回密码时校验；验证码登录成功后显式销毁 |
| 登录/注册验证码 | `SystemSmsCode` + `SmsCodeService` | 发送记录有类型、接收方、状态、错误次数和发送状态；按配置过期 |
| 密码 | `SystemUser.password` | 保存哈希，不保存明文；密码规则由 `UserService` 强制执行 |
| Web 登录态 | Session `user_id` | 浏览器凭 Session Cookie 保持；适配器每次请求重新加载用户 |
| App 登录态 | Redis Token 或 `tao_system_user_login` | 默认 1 年滑动有效期；有效请求续期，主动 logout 立即撤销 |
| OAuth 目标地址 | Session `_redirect` | OAuth 发起时保存，成功回调后由 `RedirectUtil::read()` 消费 |
| 第三方绑定 | `SystemUserBind` | OAuth 新用户流程通过 `addBinds()` 记录平台类型 |

`SmsCodeService` 还将“注册”“登录”“忘记密码”等验证码按 `kind` 区分，避免不同用途的验证码混用。

## 8. 当前实现中需要特别注意的点

以下是阅读当前代码得到的行为差异或安全边界，不是对设计意图的替代：

1. **重定向参数存在两套命名。** [`auth.js`](../views/layui/auth/auth.js) 和 `RedirectUtil` 使用 `_redirect`，但 `index.phtml` 的密码登录成功分支检查的是 `redirect`。这可能导致密码登录无法按 OAuth 的约定跳转；而且 `index.phtml` 的客户端跳转没有经过 `Oauth3Controller` 中的同源校验。
2. **注册成功后的链接疑似指向不存在的 Action。** `signup.phtml` 使用 `tao/auth/login`，但当前 `AuthController` 的登录 Action 名称是 `indexAction`；路由表只把 `/login` 映射到 `/m/tao/auth/index`。按当前代码应改为 `tao/auth/index` 或明确增加别名。
3. **OAuth 异常日志不应包含完整配置。** `Oauth3Controller` 捕获异常时把 `$config` 放入日志上下文，而该配置包含 Provider 的客户端密钥；生产日志应脱敏或不记录整个配置。
4. **第三方绑定记录目前主要是平台标记。** `UserService::addBinds()` 当前只写入 `user_id` 和 `platform`，没有把 Provider 返回的第三方用户标识写入绑定记录；如果后续需要防止账号错绑，应补充并校验第三方唯一标识。
5. **账号状态校验已覆盖主要入口，但仍应持续收敛。** `LoginAuthHelper`、Web 验证码登录、App/Open 登录和 OAuth 登录现在都会检查 `activeStatus()`；新增认证入口时仍应复用这套校验。
6. **客户端校验不是安全边界。** 协议勾选、确认密码、倒计时等都只改善交互；密码、验证码、账号状态、权限和重置签名必须由服务端再次校验。

## 9. 相关源码

- 认证控制器：[`AuthController.php`](../Controllers/AuthController.php)、[`Oauth3Controller.php`](../Controllers/Oauth3Controller.php)
- 认证视图：[`index.phtml`](../views/layui/auth/index.phtml)、[`signin.phtml`](../views/layui/auth/signin.phtml)、[`signup.phtml`](../views/layui/auth/signup.phtml)、[`forgot.phtml`](../views/layui/auth/forgot.phtml)、[`password.phtml`](../views/layui/auth/password.phtml)、[`quickLogin.phtml`](../views/layui/auth/quickLogin.phtml)、[`auth.js`](../views/layui/auth/auth.js)
- 认证适配器：[`LoginAuthHelper.php`](../Helper/LoginAuthHelper.php)、[`LoginSessionAuthAdapter.php`](../Helper/Auth/LoginSessionAuthAdapter.php)、[`LoginAppAuthAdapter.php`](../Helper/Auth/LoginAppAuthAdapter.php)、[`LoginAppDbAuthAdapter.php`](../Helper/Auth/LoginAppDbAuthAdapter.php)、[`AuthDbData.php`](../Helper/Auth/AuthDbData.php)、[`AuthSignature.php`](../Helper/Auth/AuthSignature.php)、[`AuthReplayGuard.php`](../Helper/Auth/AuthReplayGuard.php)
- 用户和验证码服务：[`UserService.php`](../Services/UserService.php)、[`SmsCodeService.php`](../Services/SmsCodeService.php)
- 授权和跳转：[`BaseRbacController.php`](../BaseRbacController.php)、[`RedirectUtil.php`](../utils/RedirectUtil.php)、[`routes/web.php`](../../../../routes/web.php)
- OAuth 配置：[`RegisterHelper.php`](../Helper/RegisterHelper.php)、[`SdkHelper.php`](../sdk/SdkHelper.php)
