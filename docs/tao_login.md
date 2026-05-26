# Tao 模块 - 用户注册登录接入文档

本文档详细说明 tao 模块的用户注册、登录认证体系，包括 Web 端、小程序端、第三方 OAuth 登录及微信小程序登录的完整接入指南。

## 1. 总体概述

### 路由前缀

| 端 | 路由前缀 | 说明 |
|---|---|---|
| Web 端 | `/m/tao/` | 基于 Session/Cookie 的传统 Web 认证 |
| 开放平台 | `/api/m/tao.open/` | 基于 Redis Token + 签名的 API 认证 |
| 微信相关 | `/api/m/tao.open/weixin.*` | 微信小程序/公众号专用 |

### 认证方式概览

| 认证方式 | 适用场景 | 适配器类 | Token 存储 |
|---|---|---|---|
| Session/Cookie | Web 浏览器 | `LoginSessionAuthAdapter` | Session |
| APP Token + 签名 | 小程序/移动端 API | `LoginAppAuthAdapter` | Redis |
| 第三方 OAuth | Google 等 | 通过 `LoginSessionAuthAdapter` | Session |
| 测试 Token | PHPUnit 测试 | `LoginDemoTokenAuthAdapter` | 内存 |

### 账号类型

系统支持两种账号标识：
- **手机号**：需通过短信验证码验证后激活（`phone_valid=1`）
- **邮箱**：需通过邮件验证码验证后激活（`email_valid=1`）

### 第三方绑定类型

| 常量 | 值 | 说明 |
|---|---|---|
| `Data::Gmail` | `gmail` | Google 账号 |
| `Data::WechatMini` | `wechatMini` | 微信小程序 |
| `Data::WechatOfficial` | `wechatOfficial` | 微信公众号 |
| `Data::TiktokMini` | `tiktokMini` | 抖音小程序 |

---

## 2. 统一响应格式

所有接口返回 JSON 格式：

**成功响应：**
```json
{
  "code": 0,
  "msg": "操作成功提示信息",
  "data": {}
}
```

**失败响应：**
```json
{
  "code": 500,
  "msg": "错误提示信息",
  "data": []
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `code` | int | `0` 表示成功，非 `0` 表示失败 |
| `msg` | string | 提示信息 |
| `data` | mixed | 响应数据，成功时为对象/数组，失败时为空数组 |

---

## 3. 认证适配器体系

系统根据请求类型自动选择认证适配器，优先级如下：

1. **JSON Body 请求**（`?data=jsonbody`）→ `LoginAppAuthAdapter`
2. **PHPUnit 测试请求**（Header 含 `test-token`）→ `LoginDemoTokenAuthAdapter`
3. **Header 含 `Authorization`** → `LoginAppAuthAdapter`
4. **默认** → `LoginSessionAuthAdapter`

---

## 4. Web 端接口

### 4.1 获取图形验证码

用于注册、登录、忘记密码等操作前的人机验证。

**请求地址：** `GET /m/tao/captcha`

**请求参数：** 无

**响应：** 直接返回图片内容（Content-Type: image/png）

**使用说明：**
- 验证码通过 Session 存储，前端通过 `<img src="/m/tao/captcha">` 直接展示
- 验证码在比对后自动销毁

---

### 4.2 用户密码登录

**请求地址：** `POST /m/tao/auth`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 登录账号，支持手机号或邮箱 |
| `password` | string | 是 | 登录密码（最少 8 位，需包含字母和数字） |
| `captcha` | string | 是 | 图形验证码 |

**成功响应：**
```json
{
  "code": 0,
  "msg": "登录成功",
  "data": "1:web:1700000000"
}
```

| 字段 | 说明 |
|---|---|
| `data` | Session 模式下的 token 标识，格式为 `userId:web:timestamp` |

**失败场景：**

| 错误信息 | 说明 |
|---|---|
| 账号不存在或密码不正确 | 账号未注册或密码错误 |
| 密码错误 | 密码校验不通过 |
| 当前账号已经被禁止登录 | 账号状态为 `STATUS_DELETE(100)` |
| 验证码错误 | 图形验证码不匹配 |

**特殊说明：**
- 登录成功后 Session 中写入 `user_id`
- 如果用户已登录，访问此页面会自动跳转

---

### 4.3 发送登录验证码

**请求地址：** `POST /m/tao/auth/signinCode`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 手机号或邮箱 |
| `captcha` | string | 是 | 图形验证码 |

**成功响应：**
```json
{
  "code": 0,
  "msg": "登录验证码已发送，请注意查收",
  "data": null
}
```

**限制：**
- 同一账号每天至多发送 3 次登录验证码
- 如果上一次验证码仍有效（未过期），不会重复发送，直接返回成功
- 手机号通过短信发送，邮箱通过邮件发送

---

### 4.4 验证码登录

**请求地址：** `POST /m/tao/auth/signin`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 手机号或邮箱 |
| `vercode` | string | 是 | 收到的验证码（4 位数字） |

**成功响应：**
```json
{
  "code": 0,
  "msg": "登录成功",
  "data": "1:web:1700000000"
}
```

**失败场景：**

| 错误信息 | 说明 |
|---|---|
| 没有找到符合条件的账号 | 账号未注册或未激活 |
| 验证码不存在或者已经过期了 | 验证码已失效 |
| 验证码错误 | 验证码不匹配 |

**前置条件：**
- 账号必须已注册且已激活（`phone_valid=1` 或 `email_valid=1`）

---

### 4.5 发送注册验证码

**请求地址：** `POST /m/tao/auth/signupCode`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 手机号或邮箱 |
| `captcha` | string | 是 | 图形验证码 |

**成功响应：**
```json
{
  "code": 0,
  "msg": "验证码已发送，请注意查收",
  "data": null
}
```

**限制：**
- 同一账号每天至多发送 3 次注册验证码
- 如果账号已被占用且已激活，不会抛出错误（出于安全考虑），但仍可能发送失败
- 验证码为 4 位随机数字（1000-9999）

---

### 4.6 账号注册

**请求地址：** `POST /m/tao/auth/signup`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 手机号或邮箱 |
| `vercode` | string | 是 | 注册验证码 |
| `password` | string | 是 | 登录密码（最少 8 位，需包含字母和数字） |

**成功响应：**
```json
{
  "code": 0,
  "msg": "账号注册成功",
  "data": null
}
```

**失败场景：**

| 错误信息 | 说明 |
|---|---|
| 不是一个合法的账号 | 账号不是有效的手机号或邮箱 |
| 邮箱已经被占用 | 邮箱已注册且已激活 |
| 手机号已经被占用 | 手机号已注册且已激活 |
| 密码最少为8位 | 密码长度不足 |
| 密码必须包含字母 | 密码缺少字母 |
| 密码必须包含数字 | 密码缺少数字 |
| 验证码不存在或者已经过期了 | 验证码已失效 |
| 验证码错误 | 验证码不匹配 |

**注册流程：**
1. 先调用 [发送注册验证码](#45-发送注册验证码) 获取验证码
2. 用户收到验证码后提交注册
3. 注册成功后账号自动激活（`phone_valid=1` 或 `email_valid=1`）
4. 密码使用 Phalcon Security 的 `hash()` 加密存储
5. 系统自动生成 `seed`（8位随机字符串）和 `puid`（30位随机字符串）

---

### 4.7 忘记密码（发送重置邮件）

**请求地址：** `POST /m/tao/auth/forgot`

**请求参数（POST）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 已注册的邮箱地址 |
| `captcha` | string | 是 | 图形验证码 |

**成功响应：**
```json
{
  "code": 0,
  "msg": "重置密码邮件已发送，请注意查收",
  "data": null
}
```

**限制：**
- 仅支持邮箱重置，不支持手机号
- 同一邮箱每天至多发送 3 次重置邮件
- 邮件中的链接 2 小时内有效
- 如果上一次链接仍有效（2 小时内），不会重复发送

---

### 4.8 通过邮件链接重置密码

**请求地址：** `GET/POST /m/tao/auth/password`

**GET 请求参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `type` | string | 是 | 固定值 `forgot` |
| `sign` | string | 是 | 签名，格式为 `md5(code + user_id + app_key)` |
| `id` | int | 是 | 验证码记录 ID |

**POST 请求参数（重置密码时）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `password` | string | 是 | 新密码（最少 8 位，需包含字母和数字） |

**成功响应：**
```json
{
  "code": 0,
  "msg": "重置密码成功",
  "data": null
}
```

**流程说明：**
1. 用户点击邮件中的链接，GET 请求到达此接口
2. 系统验证 `sign` 和 `id` 的合法性
3. 用户在页面输入新密码，POST 提交重置

---

### 4.9 Web 端退出登录

**请求地址：** `POST /m/tao/user/index/logout`

**请求参数：** 无

**成功响应：**
```json
{
  "code": 0,
  "msg": "退出登录成功",
  "data": "/"
}
```

**说明：** 退出后 Session 被销毁

---

## 5. 第三方 OAuth 登录（Google 等）

基于 [HybridAuth](https://hybridauth.github.io/) 库实现，支持 Google 等第三方 OAuth 登录。

**请求地址：** `GET /m/tao/oauth3`

**请求参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `d` | string | 是 | 驱动名称，如 `google` |
| `_redirect` | string | 否 | 授权后跳转地址 |
| `state` | string | 否 | OAuth state 参数（回调时自动携带） |

**支持的 Provider：**

| 驱动名称 (`d`) | 说明 |
|---|---|
| `google` | Google 账号登录 |

> 需在系统配置中启用对应的 Provider 并配置 `clientId`/`clientSecret`。

**登录流程：**

```
1. 前端访问: /m/tao/oauth3?d=google&_redirect=/dashboard
2. 系统重定向到 Google 授权页面
3. 用户授权后，Google 回调到: /m/tao/oauth3?d=google&state=xxx&code=xxx
4. 系统通过 HybridAuth 获取用户 Profile
5. 自动匹配或注册账号：
   - 如果 Profile 中的 email 已存在，直接关联
   - 如果 Profile 中的 phone 已存在，直接关联
   - 否则自动注册新用户，绑定类型标记为 `gmail`
6. 自动登录并跳转到之前保存的 `_redirect` 地址
```

**自动注册逻辑：**
- 新注册用户会自动设置 `nickname`（来自 `displayName`）和 `head_img`（来自 `photoURL`）
- 绑定类型会被写入 `binds` 字段，值为 `["gmail"]`

**错误场景：**

| 错误信息 | 说明 |
|---|---|
| 请求参数错误 d=driver | 未指定驱动 |
| 匹配不到 Provider | 驱动名称不在配置中 |
| 未启用的授权 Provider | Provider 未启用 |
| xxx 授权错误，请查看日志 | OAuth 授权过程异常 |

---

## 6. 微信小程序登录

### 6.1 code2Session 登录

微信小程序通过 `wx.login()` 获取 code，然后调用此接口完成登录。

**请求地址：** `POST /api/m/tao.open/weixin.mini/code2session`

**Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 小程序的 AppID |

**Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `code` | string | 是 | `wx.login()` 返回的登录凭证 |
| `userInfo` | object | 否 | 用户信息对象 |
| `userInfo.avatarUrl` | string | 否 | 用户头像 URL |
| `userInfo.nickName` | string | 否 | 用户昵称 |
| `userInfo.gender` | int | 否 | 性别：0 未知 / 1 男 / 2 女 |

**请求示例：**

```
POST /api/m/tao.open/weixin.mini/code2session?appid=wx1234567890
Content-Type: application/json

{
  "code": "0a1B2c3D4e5F6g7H8i9J0k",
  "userInfo": {
    "avatarUrl": "https://wx.qlogo.cn/xxx",
    "nickName": "张三",
    "gender": 1
  }
}
```

**成功响应：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "user_id": 123,
    "nickname": "张三",
    "avatar_url": "https://wx.qlogo.cn/xxx",
    "openid": "oXXXX-xxxxxxxxxxxxxxxxx",
    "ts": "123.app.1700000000-a1b2c3d4e5f6g7h8i9j0"
  }
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `user_id` | int | 系统用户 ID |
| `nickname` | string | 用户昵称 |
| `avatar_url` | string | 用户头像 |
| `openid` | string | 微信 openid |
| `ts` | string | 登录凭证，格式为 `token-secret`，用于后续 API 请求鉴权 |

**登录凭证使用方式：**

登录成功后返回的 `ts` 字段需拆分为 `token` 和 `secret` 两部分，用于后续所有 API 请求的鉴权：

```javascript
// 小程序端示例：解析登录凭证
const ts = res.data.ts; // 如 "1.app.1700000000-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
const dashIndex = ts.lastIndexOf('-');
const token = ts.substring(0, dashIndex);  // "1.app.1700000000"
const secret = ts.substring(dashIndex + 1); // "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"

// 存储到本地
wx.setStorageSync('auth_token', token);
wx.setStorageSync('auth_secret', secret);
```

**后续请求鉴权示例：**

每次 API 请求需在 HTTP Header 中携带 `Authorization`：

```javascript
// 封装请求方法
function request(options) {
  const token = wx.getStorageSync('auth_token');
  const secret = wx.getStorageSync('auth_secret');
  const t = Date.now(); // 当前毫秒时间戳
  const sign = md5(secret + t); // 计算签名: md5(secret + timestamp)

  wx.request({
    url: options.url,
    method: options.method || 'GET',
    data: options.data,
    header: {
      'Content-Type': 'application/json',
      'Authorization': JSON.stringify({ token, t, sign })
    },
    success: options.success,
    fail: options.fail
  });
}

// 使用示例：获取用户信息
request({
  url: 'https://example.com/api/m/tao.open/user/info?data=jsonbody&appid=wx1234567890',
  method: 'GET',
  success(res) {
    console.log(res.data);
  }
});
```

> 注意：`md5` 函数需自行引入小程序端 MD5 库。Token 过期时间为 7 天，过期后需重新登录获取。

**登录流程：**

```
1. 小程序调用 wx.login() 获取 code
2. 将 code 和 appid 发送到本接口
3. 后端调用微信 code2Session API 获取 session_key + openid + unionid
4. 系统根据 openid/unionid 查找或自动注册用户：
   a. 如果有 unionid，先查 unionid 记录
      - 找到：直接返回对应用户
      - 未找到：继续查 openid 记录
   b. 如果没有 unionid，直接查 openid 记录
   c. 如果都未找到：自动注册新用户
5. 返回用户信息 + ts 登录凭证
```

**自动注册逻辑：**
- 新用户会自动创建 `SystemUser` 记录
- 同时创建 `OpenUserOpenid` 和 `OpenUserUnionid`（如有 unionid）记录
- 绑定类型标记为 `wechatMini`
- 用户昵称和头像来自前端传入的 `userInfo`

**抖音小程序：**
- 同一接口也支持抖音小程序登录
- 通过 `appid` 对应的应用配置中 `platform` 字段区分（`Wechat=1` 或 `Tiktok=2`）
- 抖音使用不同的 API 端点 `api/apps/v2/jscode2session`

---

### 6.2 获取小程序码

**请求地址：** `POST /api/m/tao.open/weixin.mini/qRCode`

**Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 小程序的 AppID |

**Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `scene` | string | 是 | 场景值，最大 32 个字符 |
| `page` | string | 否 | 跳转页面路径，如 `pages/index/index` |
| `check_path` | bool | 否 | 是否检查页面路径 |
| `env_version` | string | 否 | 版本：`release` / `trial` / `develop` |
| `width` | int | 否 | 二维码宽度 |
| `auto_color` | bool | 否 | 自动配色 |
| `line_color` | object | 否 | 线条颜色 |

**响应：** 直接返回图片内容（Content-Type: image/jpeg）

**参考文档：** [获取不限制的小程序码](https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/qr-code/getUnlimitedQRCode.html)

---

## 7. 微信公众号授权登录

### 7.1 发起授权

**请求地址：** `GET /api/m/tao.open/weixin.auth`

**请求参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 公众号/网页应用的 AppID |
| `scope` | string | 否 | 授权方式，默认 `snsapi_base` |
| `target` | string | 否 | 授权后跳转地址，默认 `/` |
| `user` | string | 否 | 传入任意值表示仅检查登录状态 |

**scope 取值：**

| 值 | 说明 | 适用 appid kind |
|---|---|---|
| `snsapi_base` | 静默授权，仅获取 openid | `gzh`（公众号） |
| `snsapi_userinfo` | 弹窗授权，获取用户信息 | `gzh`（公众号） |
| `snsapi_login` | 网页扫码登录 | `web`（网页应用） |

**流程说明：**

1. 如果在非微信浏览器中访问，会显示二维码页面
2. 在微信浏览器中，自动跳转到微信授权页面
3. 用户授权后，微信回调到 `/api/m/tao.open/weixin.auth/code`
4. 系统通过 code 获取用户信息，并跳转到 `target` 地址，同时携带 `openid` 和 `appid`

### 7.2 授权回调

**请求地址：** `GET /api/m/tao.open/weixin.auth/code`

**请求参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 应用 AppID |
| `code` | string | 是 | 微信返回的授权 code |
| `target` | string | 否 | 最终跳转地址 |

**说明：** 此接口由微信回调自动触发，开发者通常不需要直接调用。

### 7.3 公众号事件处理

**请求地址：** `GET/POST /api/m/tao.open/weixin.official`

**请求参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 公众号 AppID |
| `skip` | string | 否 | 如果存在，直接返回空（用于跳过处理） |
| `echostr` | string | 否 | 微信服务器验证时携带 |

**说明：**
- 用于接收微信公众号的消息推送和事件推送
- 支持 `subscribe`（关注）事件：自动创建/更新用户记录
- 支持 `unsubscribe`（取消关注）事件：标记 `sub=0`
- 用于微信服务器 URL 验证

---

## 8. 小程序端 API 接口

小程序端接口通过 `?data=jsonbody` 参数标识，请求体为 JSON 格式，响应也为 JSON 格式。

### 8.1 小程序端账号密码登录

**请求地址：** `POST /api/m/tao.open/auth/login?data=jsonbody`

**Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `account` | string | 是 | 手机号或邮箱 |
| `password` | string | 是 | 登录密码（最少 8 位，需包含字母和数字） |
| `captcha` | object | 条件必填 | 验证码对象（首次不传） |

**两步式验证码流程：**

验证码采用服务端 Session 存储，答案不暴露给客户端。

**第一步：不传 captcha，获取验证码规则**
```json
// 请求
{ "account": "user@example.com", "password": "Abc12345" }

// 响应
{
  "code": 0,
  "msg": "",
  "data": {
    "rule": "23+45=?"
  }
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `rule` | string | 算术验证码题目，用户需计算答案 |

**第二步：提交验证码答案**
```json
// 请求
{
  "account": "user@example.com",
  "password": "Abc12345",
  "captcha": {
    "value": 68
  }
}
```

| captcha 字段 | 类型 | 说明 |
|---|---|---|
| `value` | int/string | 用户计算的验证码答案 |

**成功响应：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "user_id": 1,
    "nickname": "用户昵称",
    "avatar_url": "https://example.com/avatar.jpg",
    "ts": "1.app.1700000000-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
  }
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `user_id` | int | 系统用户 ID |
| `nickname` | string | 用户昵称 |
| `avatar_url` | string | 用户头像 |
| `ts` | string | 登录凭证，格式 `token-secret`，用于后续 API 请求鉴权 |

**验证码校验逻辑：**
- 验证码答案存储在服务端 Session 中，客户端仅获取算术题
- 验证码有效期为 120 秒
- 同一验证码最多允许 3 次错误尝试，超过后需重新获取
- 验证通过后立即销毁，不可重复使用

---

### 8.2 PUID 统一平台登录

通过统一平台码（PUID）快速登录，适用于系统间用户传递。

**请求地址：** `POST /api/m/tao.open/auth/puid?data=jsonbody`

**Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `puid` | string | 是 | 统一平台码，格式 `puid.userId` |
| `captcha` | object | 条件必填 | 验证码对象（首次不传） |

**两步式验证码流程：**

与登录接口相同，PUID 登录同样需要先获取验证码再提交。

**第一步：不传 captcha，获取验证码规则**
```json
// 请求
{ "puid": "AbCdEfGh.1" }

// 响应
{
  "code": 0,
  "msg": "",
  "data": {
    "rule": "17+28=?"
  }
}
```

**第二步：提交验证码答案**
```json
// 请求
{
  "puid": "AbCdEfGh.1",
  "captcha": {
    "value": 45
  }
}
```

**成功响应：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "user_id": 1,
    "nickname": "用户昵称",
    "avatar_url": "https://example.com/avatar.jpg",
    "ts": "1.app.1700000000-a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
  }
}
```

**说明：**
- PUID 码可通过 Web 端 `/m/tao/user/index/puid` 获取
- 格式为 `puid.id`，其中 puid 是 30 位随机字符串，id 是用户 ID
- 用户的 `status` 必须为 1（正常状态）
- 需要通过验证码校验，防止 PUID 暴力破解

---

### 8.3 小程序端退出登录

**请求地址：** `POST /api/m/tao.open/user/logout?data=jsonbody`

**请求参数：** 无需额外参数（通过 Authorization Header 鉴权）

**成功响应：** `"退出成功"`

**说明：** 退出时会删除 Redis 中的 token 记录

---

### 8.4 小程序端用户信息

**请求地址：** `GET/POST /api/m/tao.open/user/info?data=jsonbody&appid=xxx`

**Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 应用 AppID |

**GET 响应（获取用户信息）：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "user_id": 1,
    "avatar_url": "https://example.com/avatar.jpg",
    "nickname": "张三",
    "openid": "oXXXX-xxxxxxxxxxxxxxxxx"
  }
}
```

**POST 请求（修改用户信息）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `name` | string | 是 | 字段名，仅支持 `avatar_url` 或 `nickname` |
| `value` | string | 是 | 新的值 |

---

## 9. APP Token 签名机制

小程序端/API 端使用 `LoginAppAuthAdapter` 进行认证，基于 Redis Token + 签名验证。

### 9.1 获取 Token

登录成功后，系统返回 `ts` 字段，格式为 `token-secret`：

```
ts = "{token}-{secret}"
```

| 部分 | 格式 | 示例 | 说明 |
|---|---|---|---|
| token | `{userId}.app.{timestamp}` | `1.app.1700000000` | 登录标识，存储在 Redis 中 |
| secret | 32 位 MD5 字符串 | `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6` | 签名密钥 |

> **重要**：`ts` 中的 `-` 分隔符可能出现在 `token` 部分（如 userId 为负数时），因此拆分时必须使用 **最后一个 `-`** 作为分隔符。

### 9.2 请求鉴权

后续 API 请求需在 HTTP Header 中携带 `Authorization`：

```http
Authorization: {"token":"1.app.1700000000","t":1700000123,"sign":"b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7"}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `token` | string | 登录时返回的 token 部分 |
| `t` | int | 当前时间戳（毫秒级） |
| `sign` | string | 签名，计算方式 `md5(secret + t)` |

**签名计算示例：**

```
secret = "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
t = 1700000123
sign = md5("a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p61700000123")
```

### 9.3 小程序端完整接入示例

```javascript
// ============ auth.js - 认证工具模块 ============

const AUTH_TOKEN_KEY = 'auth_token';
const AUTH_SECRET_KEY = 'auth_secret';

/**
 * 保存登录凭证
 */
function saveLoginToken(ts) {
  const dashIndex = ts.lastIndexOf('-');
  const token = ts.substring(0, dashIndex);
  const secret = ts.substring(dashIndex + 1);
  wx.setStorageSync(AUTH_TOKEN_KEY, token);
  wx.setStorageSync(AUTH_SECRET_KEY, secret);
}

/**
 * 获取 Authorization Header
 */
function getAuthHeader() {
  const token = wx.getStorageSync(AUTH_TOKEN_KEY);
  const secret = wx.getStorageSync(AUTH_SECRET_KEY);
  if (!token || !secret) return null;

  const t = Date.now();
  const sign = md5(secret + t);
  return { token, t, sign };
}

/**
 * 封装带鉴权的请求
 */
function request(options) {
  const auth = getAuthHeader();

  if (!auth && !options.noAuth) {
    // 未登录，跳转到登录页
    wx.redirectTo({ url: '/pages/login/login' });
    return;
  }

  const header = {
    'Content-Type': 'application/json',
    ...(auth ? { Authorization: JSON.stringify(auth) } : {})
  };

  wx.request({
    url: options.url,
    method: options.method || 'GET',
    data: options.data,
    header: header,
    success(res) {
      if (res.statusCode === 401 || res.statusCode === 403) {
        // Token 过期，清除本地存储并跳转登录
        wx.removeStorageSync(AUTH_TOKEN_KEY);
        wx.removeStorageSync(AUTH_SECRET_KEY);
        wx.redirectTo({ url: '/pages/login/login' });
        return;
      }
      if (options.success) options.success(res);
    },
    fail: options.fail
  });
}

/**
 * 清除登录状态
 */
function logout() {
  wx.removeStorageSync(AUTH_TOKEN_KEY);
  wx.removeStorageSync(AUTH_SECRET_KEY);
}

module.exports = { saveLoginToken, getAuthHeader, request, logout };
```

```javascript
// ============ 登录页调用示例 ============

const auth = require('../../utils/auth');

// 微信登录
wx.login({
  success(loginRes) {
    wx.request({
      url: 'https://example.com/api/m/tao.open/weixin.mini/code2session?appid=wx1234567890',
      method: 'POST',
      data: {
        code: loginRes.code,
        userInfo: { nickName: '用户', gender: 0 }
      },
      success(res) {
        if (res.data.code === 0) {
          // 保存登录凭证
          auth.saveLoginToken(res.data.data.ts);
          console.log('登录成功，用户ID:', res.data.data.user_id);
        }
      }
    });
  }
});

// 账号密码登录（两步式验证码）
function loginWithPassword(account, password) {
  // 第一步：获取验证码
  wx.request({
    url: 'https://example.com/api/m/tao.open/auth/login?data=jsonbody',
    method: 'POST',
    data: { account, password },
    success(res) {
      if (res.data.data && res.data.data.rule) {
        // 展示验证码给用户输入
        const rule = res.data.data.rule; // 如 "23+45=?"
        showCaptchaInput(rule, function(userAnswer) {
          // 第二步：提交验证码答案
          wx.request({
            url: 'https://example.com/api/m/tao.open/auth/login?data=jsonbody',
            method: 'POST',
            data: {
              account, password,
              captcha: { value: userAnswer }
            },
            success(res2) {
              if (res2.data.code === 0) {
                auth.saveLoginToken(res2.data.data.ts);
                console.log('登录成功');
              }
            }
          });
        });
      }
    }
  });
}

// 后续业务请求
auth.request({
  url: 'https://example.com/api/m/tao.open/user/info?data=jsonbody&appid=wx1234567890',
  method: 'GET',
  success(res) {
    console.log('用户信息:', res.data);
  }
});
```

### 9.4 Token 生命周期

| 配置项 | 值 | 说明 |
|---|---|---|
| 默认过期时间 | 7 天（604800 秒） | Redis key 的 TTL |
| 自动续期 | 剩余 TTL < 2 天时 | 请求时自动续期至 7 天 |

### 9.5 Token 错误处理

| 错误信息 | HTTP 状态码 | 说明 |
|---|---|---|
| 登录凭证过期或不存在. | 401 | Authorization Header 格式错误 |
| 登录凭证过期或不存在 | 403 | token 已过期或被删除 |
| 签名验证失败 | 200 | sign 计算不匹配 |
| 用户登录凭证错误:1 | 200 | token 格式不正确 |
| 用户登录凭证错误:2 | 200 | token 中的 kind 不匹配 |

---

## 10. 错误码说明

| code | 说明 |
|---|---|
| `0` | 成功 |
| `401` | 登录凭证格式错误（Header 解析失败） |
| `403` | 登录凭证已过期 |
| `500` | 一般性业务错误 |

---

## 11. 接入注意事项

### 前置配置

1. **短信/邮件服务**：需在系统配置（`tao_system_config` 表 `sms` 分组）中配置短信和邮件发送参数
2. **Redis**：APP Token 模式依赖 Redis，需配置 `session.stores.redis`
3. **微信小程序**：需在 `tao_open_app` 表中配置小程序的 `appid`、`secret`、`kind=mini`、`platform=1`
4. **微信公众号**：需在 `tao_open_app` 表中配置公众号的 `appid`、`secret`、`kind=gzh`/`dyh`/`fwh`、`platform=1`
5. **Google OAuth**：需在系统配置中启用并配置 Google Provider 的 `clientId`/`clientSecret`

### 安全要点

- 验证码每天有发送次数限制（各类型均为 3 次/天）
- 图形验证码比对后自动销毁
- 登录验证码比对后自动销毁
- 验证码错误次数达到上限后立即失效（最多 3 次错误）
- 密码使用 Phalcon Security 的 `hash()` 进行单向加密
- 密码强度要求：最少 8 位，必须包含字母和数字
- 修改手机号/邮箱有 30 天冷却期
- APP Token 的签名机制防止 token 被盗用
- 修改账号信息需验证码校验
- 小程序端验证码答案存储在服务端 Session，不暴露给客户端
- 重置密码链接签名包含应用密钥，防止签名被暴力破解
- 重定向地址仅允许同域，防止开放重定向攻击

### 用户数据模型

关键字段说明（`tao_system_user` 表）：

| 字段 | 类型 | 说明 |
|---|---|---|
| `id` | int | 用户 ID |
| `phone` | string | 手机号 |
| `phone_valid` | int | 手机号是否验证：0 未验证 / 1 已验证 |
| `email` | string | 邮箱 |
| `email_valid` | int | 邮箱是否验证：0 未验证 / 1 已验证 |
| `password` | string | 加密后的密码 |
| `nickname` | string | 昵称 |
| `head_img` | string | 头像 URL |
| `binds` | string | JSON 数组，第三方绑定类型 |
| `status` | int | 状态：1 正常 / 100 禁用 |
| `puid` | string | 统一平台码（30位随机字符串） |
| `role_ids` | string | 角色 ID 列表，逗号分隔 |

### 开放平台数据模型

| 表 | 说明 |
|---|---|
| `tao_open_user_openid` | 用户 openid 映射，每个 appid + openid 对应一条记录 |
| `tao_open_user_unionid` | 用户 unionid 映射，跨应用统一用户标识 |
| `tao_open_app` | 应用配置，存储各平台小程序/公众号的 appid/secret |

### 短信模板

| 用途 | 模板 Code | 说明 |
|---|---|---|
| 注册 | `SMS_177005595` | 验证码${code}，您正在注册成为新用户 |
| 登录 | `SMS_177005597` | 验证码${code}，您正在登录 |
| 修改密码 | `SMS_177005594` | 验证码${code}，您正在尝试修改登录密码 |
| 修改账号 | `SMS_177005593` | 验证码${code}，您正在尝试变更重要信息 |
