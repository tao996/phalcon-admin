# Tao 模块 - 完整接口文档

本文档涵盖 tao 模块所有控制器的接口说明，包括请求地址、参数、响应格式和使用细节。

> 用户注册登录相关接口详见 [tao_login.md](tao_login.md)，此处不再重复。

---

## 1. 总体说明

### 1.1 路由规则

| 模块 | 路由前缀 | 说明 |
|---|---|---|
| 核心 tao | `/m/tao/` | Web 端后台管理 |
| 开放平台 | `/api/m/tao.open/` | 小程序/API 端 |
| CMS | `/api/m/tao.cms/` | 内容管理 |
| 微信 | `/api/m/tao.open/weixin.*` | 微信小程序/公众号 |
| 微信支付 | `/api/m/tao.wechat/` | 微信支付相关 |
| 应用 | `/api/m/tao.app/` | 应用信息管理 |

### 1.2 路由格式

```
/{前缀}/{控制器路径}/{action名称}
```

控制器路径中的目录分隔符用 `.` 表示，例如：
- `Controllers/admin/ConfigController.php` → `/m/tao/admin.config/{action}`
- `Controllers/user/IndexController.php` → `/m/tao/user.index/{action}`
- `A0/open/Controllers/weixin/MiniController.php` → `/api/m/tao.open/weixin.mini/{action}`

### 1.3 统一响应格式

**成功响应：**
```json
{ "code": 0, "msg": "提示信息", "data": {} }
```

**失败响应：**
```json
{ "code": 500, "msg": "错误信息", "data": [] }
```

**分页响应：**
```json
{ "code": 0, "msg": "", "data": { "count": 100, "rows": [] } }
```

| 字段 | 类型 | 说明 |
|---|---|---|
| `code` | int | `0` 成功，非 `0` 失败 |
| `msg` | string | 提示信息 |
| `data` | mixed | 响应数据 |

### 1.4 认证方式

| 方式 | 适用场景 | 标识 |
|---|---|---|
| Session/Cookie | Web 浏览器 | 默认 |
| APP Token + 签名 | 小程序/API | `?data=jsonbody` + JSON Body |
| 测试 Token | PHPUnit | Header `test-token` |
| Authorization Header | API | `Authorization: {token,t,sign}` |

### 1.5 权限层级

| 属性 | 说明 |
|---|---|
| `openActions = '*'` | 所有 action 无需登录 |
| `userActions = '*'` | 所有 action 需登录即可访问 |
| `superAdminActions = '*'` | 所有 action 仅超级管理员可访问 |
| `enableActions` | 白名单：仅允许的 action |
| `disableUpdateActions` | 禁用 add/edit/modify/delete |

### 1.6 通用 CRUD 接口模式

继承 `BaseController` 的控制器默认提供以下 action：

| Action | 方法 | 说明 | 默认参数 |
|---|---|---|---|
| `index` | GET | 列表查询 | `page`, `limit`, `reset` |
| `add` | GET/POST | 添加记录 | POST: 表单数据 |
| `edit` | GET/POST | 编辑记录 | `id`(Query), POST: 表单数据 |
| `delete` | POST | 删除记录 | `id`(POST, 支持数组) |
| `modify` | POST | 快捷修改字段 | `id`, `field`, `value` |

**分页参数：**

| 参数 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `page` | int | 1 | 页码 |
| `limit` | int | 15 | 每页条数 |
| `reset` | int | - | 传 `1` 忽略搜索条件 |

**modify 可修改字段：** 由控制器 `$allowModifyFields` 和 `$appendModifyFields` 决定，默认为 `['status', 'sort', 'remark']`。

---

## 2. 核心控制器

### 2.1 CaptchaController — 验证码

**路由：** `/m/tao/captcha`  
**权限：** 公开（`openActions = '*'`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/captcha` | 获取图形验证码图片 |

**响应：** 直接返回图片内容（Content-Type: image/png）

**使用：** `<img src="/m/tao/captcha">` 嵌入页面，验证码存储在 Session 中。

---

### 2.2 IndexController — 后台框架

**路由：** `/m/tao/`  
**权限：** 需登录（`userActions = '*'`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/` | 后台菜单框架 |
| `welcome` | GET | `/m/tao/welcome` | 后台欢迎页 |

**index 响应（API 模式）：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "menuTree": []
  }
}
```

| 字段 | 说明 |
|---|---|
| `menuTree` | 当前用户的菜单树 |

---

### 2.3 Oauth3Controller — 第三方 OAuth 登录

**路由：** `/m/tao/oauth3`  
**权限：** 公开（`openActions = '*'`）

> 详细文档见 [tao_login.md](tao_login.md) 第 5 节

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/oauth3` | 发起第三方 OAuth 登录 |

**参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `d` | string | 是 | 驱动名称，如 `google` |
| `_redirect` | string | 否 | 授权后跳转地址 |
| `state` | string | 否 | OAuth state（回调时携带） |

---

## 3. 管理员控制器（admin）

所有 admin 控制器需后台管理员权限。

### 3.1 ConfigController — 系统配置

**路由：** `/m/tao/admin.config`  
**模型：** `SystemConfig`  
**启用 Action：** `index`, `save`, `reload`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.config` | 配置列表（视图） |
| `save` | POST | `/m/tao/admin.config/save/{gname}` | 保存指定分组的配置 |
| `reload` | GET/POST | `/m/tao/admin.config/reload` | 重载配置缓存 |

**save 参数：**

| 参数 | 位置 | 类型 | 必填 | 说明 |
|---|---|---|---|---|
| `gname` | URL | string | 是 | 配置组名称（如 `sms`, `upload`） |
| 其他字段 | POST | mixed | 否 | 对应组内的配置项键值对 |

**save 响应：**
```json
{ "code": 0, "msg": "更新成功", "data": null }
```

**reload 响应：**
```json
{ "code": 0, "msg": "更新配置成功", "data": null }
```

---

### 3.2 MenuController — 菜单管理

**路由：** `/m/tao/admin.menu`  
**模型：** `SystemMenu`  
**可修改字段：** `sort`, `status`, `roles`, `remark`, `href`, `params`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.menu` | 菜单列表（树形） |
| `add` | GET/POST | `/m/tao/admin.menu/add` | 添加菜单 |
| `edit` | GET/POST | `/m/tao/admin.menu/edit` | 编辑菜单 |
| `delete` | POST | `/m/tao/admin.menu/delete` | 删除菜单 |
| `modify` | POST | `/m/tao/admin.menu/modify` | 快捷修改 |
| `user` | GET | `/m/tao/admin.menu/user/{userId}` | 获取指定用户菜单 |

**add/edit POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `pid` | int | 是 | 上级菜单 ID |
| `title` | string | 是 | 菜单名称 |
| `href` | string | 否 | 链接地址 |
| `icon` | string | 否 | 图标 |
| `type` | int | 否 | 链接类型（0=无, 1=项目, 2=模块） |
| `sort` | int | 否 | 排序 |
| `remark` | string | 否 | 备注 |
| `roles` | string | 否 | 可访问角色 |

**index 响应字段：** `id`, `pid`, `title`, `icon`, `href`, `type`, `sort`, `status`, `roles`, `params`

**注意：**
- 首页（homeId）不允许添加子菜单和删除
- `type=2(Module)` 时 href 不能以 `/m/` 开头
- `type=1(Project)` 时 href 不能以项目前缀开头

---

### 3.3 NodeController — 节点管理

**路由：** `/m/tao/admin.node`  
**模型：** `SystemNode`  
**启用 Action：** `index`, `reload`, `modify`  
**可修改字段：** `title`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.node` | 节点列表（树形） |
| `reload` | GET/POST | `/m/tao/admin.node/reload` | 扫描更新节点 |
| `modify` | POST | `/m/tao/admin.node/modify` | 修改节点标题 |

**reload 参数：**

| 参数 | 位置 | 类型 | 必填 | 说明 |
|---|---|---|---|---|
| `todb` | URL | bool | 否 | 传 `true`/`1` 将变更写入数据库 |

**reload 响应（预览模式，todb=false）：**
```json
{
  "code": 0,
  "msg": "",
  "data": {
    "count": 50,
    "rows": [
      { "kind": 2, "type": 1, "module": "tao", "node": "admin.config", "title": "配置管理", "is_auth": 1, "ac": 1 }
    ]
  }
}
```

| 字段 | 说明 |
|---|---|
| `ac` | 操作标记：`1`=新增(AC_INSERT), `2`=更新(AC_UPDATE) |

**流程说明：**
1. 不传 `todb`：仅扫描对比，返回差异预览
2. 传 `todb=true`：将新增、更新、删除操作写入数据库

---

### 3.4 RoleController — 角色管理

**路由：** `/m/tao/admin.role`  
**模型：** `SystemRole`  
**查询字段：** `id`, `name`, `title`, `sort`, `status`, `remark`, `created_at`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.role` | 角色列表 |
| `add` | GET/POST | `/m/tao/admin.role/add` | 添加角色 |
| `edit` | GET/POST | `/m/tao/admin.role/edit` | 编辑角色 |
| `delete` | POST | `/m/tao/admin.role/delete` | 删除角色 |
| `authorize` | GET/POST | `/m/tao/admin.role/authorize` | 角色授权 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `status` | int | 状态筛选，默认 0 |
| `name` | string | 名称模糊搜索 |

**add/edit POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `title` | string | 是 | 角色标题 |
| `name` | string | 是 | 角色标识 |
| `remark` | string | 否 | 备注 |

**authorize GET 响应：**
```json
{ "code": 0, "msg": "", "data": [1, 2, 5] }
```
返回该角色已授权的节点 ID 列表。

**authorize POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int(Query) | 是 | 角色 ID |
| `node` | array(POST) | 否 | 授权节点 ID 数组 |

---

### 3.5 UserController — 用户管理

**路由：** `/m/tao/admin.user`  
**模型：** `SystemUser`  
**查询字段：** `id`, `role_ids`, `head_img`, `nickname`, `email`, `email_valid`, `phone`, `phone_valid`, `binds`, `status`, `created_at`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.user` | 用户列表 |
| `add` | GET/POST | `/m/tao/admin.user/add` | 添加用户 |
| `edit` | GET/POST | `/m/tao/admin.user/edit` | 编辑用户 |
| `delete` | POST | `/m/tao/admin.user/delete` | 删除用户 |
| `modify` | POST | `/m/tao/admin.user/modify` | 快捷修改 |
| `password` | GET/POST | `/m/tao/admin.user/password` | 修改用户密码 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `id` | int | 用户 ID |
| `status` | int | 状态 |
| `phone` | string | 手机号模糊搜索 |
| `email` | string | 邮箱模糊搜索 |
| `created_at` | string | 创建时间范围（Layui dateRange 格式） |

**add POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `phone` | string | 条件必填 | 手机号（与邮箱至少一个） |
| `email` | string | 条件必填 | 邮箱（与手机号至少一个） |
| `password` | string | 是 | 密码 |
| `phone_valid` | bool | 否 | 手机号是否已验证 |
| `email_valid` | bool | 否 | 邮箱是否已验证 |
| `nickname` | string | 否 | 昵称 |
| `head_img` | string | 否 | 头像 URL |
| `signature` | string | 否 | 签名 |
| `role_ids` | array | 否 | 角色 ID 数组 |

**add GET 响应：**
```json
{ "auth_list": [{ "id": 1, "name": "admin", "title": "管理员" }] }
```

**edit POST 参数：** 同 add，但均为可选更新。

**password POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int | 是 | 用户 ID |
| `password` | string | 是 | 新密码 |
| `old_password` | string | 条件必填 | 旧密码（非超管必须提供） |

**注意：**
- 超级管理员不允许被修改状态或删除
- 必须设置至少一个登录账号（手机号或邮箱）

---

### 3.6 UpgradeController — 更新升级

**路由：** `/m/tao/admin.upgrade`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/admin.upgrade` | 升级页面 |
| `migration` | GET/POST | `/m/tao/admin.upgrade/migration/{project}` | 执行项目迁移 |

**migration 参数：**

| 参数 | 位置 | 类型 | 必填 | 说明 |
|---|---|---|---|---|
| `project` | URL | string | 是 | 项目名称 |

---

## 4. 用户端控制器（user）

所有 user 控制器需登录（`userActions = '*'`）。

### 4.1 IndexController — 会员中心

**路由：** `/m/tao/user.index`  
**可修改字段：** `status`, `nickname`, `head_img`, `signature`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET/POST | `/m/tao/user.index` | 基本资料查看/修改 |
| `puid` | GET | `/m/tao/user.index/puid` | 查看 PUID 码 |
| `changePhone` | GET/POST | `/m/tao/user.index/changePhone` | 修改手机号 |
| `phoneCode` | POST | `/m/tao/user.index/phoneCode` | 发送手机验证码 |
| `changeEmail` | GET/POST | `/m/tao/user.index/changeEmail` | 修改邮箱 |
| `emailCode` | POST | `/m/tao/user.index/emailCode` | 发送邮箱验证码 |
| `menu` | GET | `/m/tao/user.index/menu` | 获取用户菜单 |
| `password` | GET/POST | `/m/tao/user.index/password` | 修改密码 |
| `clear` | POST | `/m/tao/user.index/clear` | 清除个人缓存 |
| `logout` | POST | `/m/tao/user.index/logout` | 退出登录 |

**index POST 参数（修改资料）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `head_img` | string | 否 | 头像 URL（需域名校验） |
| `signature` | string | 否 | 签名 |

**puid 响应：** 返回字符串 `puid.userId`，如 `AbCdEfGh.1`

**changePhone POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `phone` | string | 是 | 新手机号 |
| `vercode` | string | 是 | 验证码 |

**phoneCode POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `phone` | string | 是 | 新手机号 |

**changeEmail POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `email` | string | 是 | 新邮箱 |
| `vercode` | string | 是 | 验证码 |

**emailCode POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `email` | string | 是 | 新邮箱 |

**menu 响应：**
```json
{
  "logoInfo": { "title": "系统名称", "image": "logo_url", "href": "/" },
  "homeInfo": {},
  "menuInfo": []
}
```

**password POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `password` | string | 是 | 新密码 |

**注意：**
- 修改手机号/邮箱有 30 天冷却期
- 退出登录会销毁 Session

---

### 4.2 FileController — 文件上传

**路由：** `/m/tao/user.file`  
**模型：** `SystemUploadfile`  
**可修改字段：** `summary`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/user.file` | 文件列表 |
| `save` | POST | `/m/tao/user.file/save` | 上传文件 |
| `editor` | POST | `/m/tao/user.file/editor` | 编辑器上传图片 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `keyword` | string | 摘要模糊搜索 |
| `status` | int | 状态筛选 |
| `page` | int | 页码 |
| `limit` | int | 每页条数 |

**index 响应字段：** `id`, `url`, `summary`, `created_at`, `width`, `height`

**save 响应：**
```json
{ "code": 0, "msg": "上传成功", "data": { "id": 1, "url": "/uploads/xxx.jpg" } }
```

**editor 响应（CKEditor 格式）：**
```json
{ "error": { "message": "上传成功", "number": 201 }, "filename": "", "uploaded": 1, "url": "/uploads/xxx.jpg" }
```

---

### 4.3 LogController — 操作日志

**路由：** `/m/tao/user.log`  
**模型：** `SystemLog`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/user.log` | 日志列表 |
| `modify` | POST | `/m/tao/user.log/modify` | 快捷修改 |
| `delete` | POST | `/m/tao/user.log/delete` | 删除日志 |

**index 响应：** 日志列表，自动关联 `nickname` 字段（通过 `user_id` 查询用户昵称）。

---

### 4.4 QiniuController — 七牛上传凭证

**路由：** `/m/tao/user.qiniu`  
**权限：** 仅 DEBUG 模式可用

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/user.qiniu` | 获取七牛上传 Token |

**响应：**
```json
{
  "token": "七牛上传Token",
  "expire": 1700007100,
  "domain": "https://cdn.example.com"
}
```

---

### 4.5 QuickController — 快捷链接

**路由：** `/m/tao/user.quick`  
**模型：** `SystemQuick`  
**可修改字段：** `sort`, `title`, `status`, `href`, `remark`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/user.quick` | 链接列表 |
| `add` | GET/POST | `/m/tao/user.quick/add` | 添加链接 |
| `edit` | GET/POST | `/m/tao/user.quick/edit` | 编辑链接 |
| `delete` | POST | `/m/tao/user.quick/delete` | 删除链接 |
| `modify` | POST | `/m/tao/user.quick/modify` | 快捷修改 |

**add/edit POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `href` | string | 是 | 链接地址 |
| `title` | string | 是 | 快捷名称 |
| `icon` | string | 否 | 图标 |
| `sort` | int | 否 | 排序 |
| `remark` | string | 否 | 备注 |

**排序规则：** `sort DESC, id DESC`

---

### 4.6 UploadfileController — 文件管理

**路由：** `/m/tao/user.uploadfile`  
**模型：** `SystemUploadfile`  
**启用 Action：** `index`, `modify`, `add`, `delete`  
**可修改字段：** `summary`  
**查询字段：** `id`, `upload_type`, `summary`, `url`, `width`, `height`, `file_size`, `created_at`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/m/tao/user.uploadfile` | 文件列表 |
| `add` | GET/POST | `/m/tao/user.uploadfile/add` | 添加文件 |
| `modify` | POST | `/m/tao/user.uploadfile/modify` | 修改文件摘要 |
| `delete` | POST | `/m/tao/user.uploadfile/delete` | 删除文件 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `title` | string | 摘要模糊搜索 |

**注意：** 非超级管理员只能查看自己上传的文件。

---

## 5. 开放平台控制器（A0/open）

### 5.1 AuthController — 小程序认证

**路由：** `/api/m/tao.open/auth`  
**基础类：** `BaseOpenMiniController`  
**公开 Action：** `puid`, `login`

> 详细文档见 [tao_login.md](tao_login.md) 第 8 节

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `puid` | POST | `/api/m/tao.open/auth/puid` | PUID 统一平台登录 |
| `login` | POST | `/api/m/tao.open/auth/login` | 账号密码登录（两步验证码） |

---

### 5.2 UserController — 小程序用户

**路由：** `/api/m/tao.open/user`  
**基础类：** `BaseOpenMiniController`  
**用户 Action：** `info`  
**公开 Action：** `logout`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `info` | GET/POST | `/api/m/tao.open/user/info` | 获取/修改用户资料 |
| `logout` | POST | `/api/m/tao.open/user/logout` | 退出登录 |

> 详细文档见 [tao_login.md](tao_login.md) 第 8.3、8.4 节

---

### 5.3 AppController — 应用管理

**路由：** `/api/m/tao.open/admin.app`  
**基础类：** `BaseOpenController`  
**模型：** `OpenApp`  
**可修改字段：** `status`, `sort`, `online`, `sandbox`  
**隐藏字段：** `secret`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.open/admin.app` | 应用列表 |
| `add` | GET/POST | `/api/m/tao.open/admin.app/add` | 添加应用 |
| `edit` | GET/POST | `/api/m/tao.open/admin.app/edit` | 编辑应用 |
| `delete` | POST | `/api/m/tao.open/admin.app/delete` | 删除应用 |
| `modify` | POST | `/api/m/tao.open/admin.app/modify` | 快捷修改 |
| `cert` | POST | `/api/m/tao.open/admin.app/cert` | 修改证书 |

**add/edit POST 参数（白名单）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 应用 AppID |
| `platform` | string | 是 | 平台（Wechat=1, Tiktok=2） |
| `title` | string | 是 | 应用名称 |
| `kind` | string | 是 | 应用类型（mini/gzh/dyh/fwh/web） |
| `secret` | string | 是 | 应用密钥 |
| `token` | string | 否 | 消息校验 Token |
| `enc_method` | string | 否 | 加密方式 |
| `aes_key` | string | 否 | 消息加解密 Key |
| `crop_id` | string | 否 | 企业 ID |
| `sandbox` | bool | 否 | 沙箱模式 |
| `remark` | string | 否 | 备注 |

**cert POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int | 是 | 应用 ID |
| `name` | string | 是 | 证书类型：`public_key` / `rsa_public_key` / `rsa_private_key` |
| `value` | string | 否 | 证书内容（为空则清除证书） |

**cert 上传：** 也支持文件上传方式，上传的证书文件会被加密存储。

**index 额外字段：** `public_key`(bool), `rsa_public_key`(bool), `rsa_private_key`(bool) — 表示是否已配置证书

**排序：** `sort DESC, id DESC`

---

### 5.4 ConfigController — 开放平台配置

**路由：** `/api/m/tao.open/admin.config`  
**基础类：** `BaseOpenController`  
**模型：** `OpenConfig`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET/POST | `/api/m/tao.open/admin.config` | 查看/更新配置 |

**GET 响应：** 返回当前所有配置键值对

**POST 参数：** 直接以键值对形式提交需修改的配置项

```json
{ "key1": "value1", "key2": "value2" }
```

---

### 5.5 MchController — 商户管理

**路由：** `/api/m/tao.open/admin.mch`  
**基础类：** `BaseOpenController`  
**模型：** `OpenMch`  
**权限：** 超级管理员（`superAdminActions = '*'`）  
**隐藏字段：** `secret_key`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.open/admin.mch` | 商户列表 |
| `add` | GET/POST | `/api/m/tao.open/admin.mch/add` | 添加商户 |
| `edit` | GET/POST | `/api/m/tao.open/admin.mch/edit` | 编辑商户 |
| `delete` | POST | `/api/m/tao.open/admin.mch/delete` | 删除商户 |
| `modify` | POST | `/api/m/tao.open/admin.mch/modify` | 快捷修改 |
| `cert` | POST | `/api/m/tao.open/admin.mch/cert` | 上传证书 |

**add/edit 必填参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `mchid` | string | 是 | 商户号 ID |
| `secret_key` | string | 是 | V3 API 秘钥 |
| `pubkey_id` | string | 是 | 微信支付公钥 ID |

**cert POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int | 是 | 商户 ID |
| `name` | string | 是 | 证书类型：`private_key` / `certificate` / `pubkey` / `platform_cert` |

**cert 上传：** 支持文件上传，证书文件以 MD5 哈希名保存。`value` 为空则清除证书。

**index 额外字段：** `private_key`(bool), `pubkey`(bool), `certificate`(bool) — 表示是否已配置

---

### 5.6 OrderController — 订单管理

**路由：** `/api/m/tao.open/admin.order`  
**基础类：** `BaseOpenController`  
**模型：** `OpenOrder`  
**查询字段：** `id`, `created_at`, `user_id`, `channel`, `trade_type`, `appid`, `mchid`, `amount`, `status`, `success_time`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.open/admin.order` | 订单列表 |
| `modify` | POST | `/api/m/tao.open/admin.order/modify` | 快捷修改 |
| `delete` | POST | `/api/m/tao.open/admin.order/delete` | 删除订单 |

---

### 5.7 PayController — 支付演示

**路由：** `/api/m/tao.open/demo.pay`  
**权限：** 公开（`openActions = '*'`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.open/demo.pay` | 支付测试入口 |
| `jsapi` | GET/POST | `/api/m/tao.open/demo.pay/jsapi` | JSAPI 支付 |
| `notify` | POST | `/api/m/tao.open/demo.pay/notify/{appid}/{mchid}` | 支付通知回调 |
| `refundNotify` | POST | `/api/m/tao.open/demo.pay/refundNotify/{outTradeNo}` | 退款通知回调 |

**index 参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 支付公众号 ID |

**jsapi 参数（Query）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 公众号 AppID |
| `openid` | string | 是 | 用户 openid |

**jsapi POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `money` | float | 否 | 金额（元），默认 0.01 |

**jsapi 响应：** 返回微信 JSAPI 支付参数，供前端调用。

**注意：** 仅支持在微信浏览器中操作。

---

## 6. 微信控制器（weixin）

### 6.1 MiniController — 小程序接口

**路由：** `/api/m/tao.open/weixin.mini`  
**基础类：** `BaseOpenMiniController`  
**权限：** 公开（`openActions = '*'`）

> 详细文档见 [tao_login.md](tao_login.md) 第 6 节

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `code2Session` | POST | `/api/m/tao.open/weixin.mini/code2session` | 小程序登录 |
| `qRCode` | POST | `/api/m/tao.open/weixin.mini/qRCode` | 获取小程序码 |

**code2Session Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 小程序 AppID |

**code2Session Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `code` | string | 是 | `wx.login()` 返回的登录凭证 |
| `userInfo` | object | 否 | `{ avatarUrl, nickName, gender }` |

**qRCode Body 参数（JSON）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `scene` | string | 是 | 场景值，最大 32 字符 |
| `page` | string | 否 | 跳转页面路径 |
| `check_path` | bool | 否 | 是否检查页面路径 |
| `env_version` | string | 否 | 版本：`release`/`trial`/`develop` |
| `width` | int | 否 | 二维码宽度 |
| `auto_color` | bool | 否 | 自动配色 |
| `line_color` | object | 否 | 线条颜色 |

**qRCode 响应：** 直接返回图片（Content-Type: image/jpeg）

---

### 6.2 OfficialController — 公众号消息

**路由：** `/api/m/tao.open/weixin.official`  
**基础类：** `BaseOpenMiniController`  
**权限：** 公开（`openActions = '*'`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET/POST | `/api/m/tao.open/weixin.official` | 接入公众号消息 |

**Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 公众号 AppID |
| `skip` | string | 否 | 存在时直接返回空 |
| `echostr` | string | 否 | 微信服务器验证参数 |

**说明：**
- GET 请求用于微信服务器 URL 验证
- POST 请求接收微信消息推送
- 支持 `subscribe`（关注）事件：自动创建/更新用户
- 支持 `unsubscribe`（取消关注）事件
- 支持 `text` 消息回复

---

### 6.3 AuthController — 公众号授权

**路由：** `/api/m/tao.open/weixin.auth`  
**基础类：** `BaseOpenController`

> 详细文档见 [tao_login.md](tao_login.md) 第 7 节

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.open/weixin.auth` | 发起公众号授权 |
| `code` | GET | `/api/m/tao.open/weixin.auth/code` | 授权回调 |

**index Query 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `appid` | string | 是 | 公众号/网页应用 AppID |
| `scope` | string | 否 | `snsapi_base`/`snsapi_userinfo`/`snsapi_login`，默认 `snsapi_base` |
| `target` | string | 否 | 授权后跳转地址，默认 `/` |
| `user` | string | 否 | 传入任意值仅检查登录状态 |

---

## 7. CMS 控制器（A0/cms）

### 7.1 OpenController — CMS 公开页面

**路由：** `/api/m/tao.cms/open`  
**权限：** `page`, `terms` 为公开 Action  
**禁用主布局：** 是

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `page` | GET | `/api/m/tao.cms/open/page/{name}` | 单页信息显示 |

**page 参数：**

| 参数 | 位置 | 类型 | 必填 | 说明 |
|---|---|---|---|---|
| `name` | URL | string | 是 | 页面标识 |
| `tag` | Query | string | 否 | 标签，默认当前项目名 |

**page 响应：** 返回单页数据（CmsPage 模型数据）

---

### 7.2 AdController — 广告管理

**路由：** `/api/m/tao.cms/admin.ad`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsAd`  
**可修改字段：** `status`, `sort`, `remark` + `at_banner`, `at_index`, `at_list`, `at_page`, `tag`, `gname`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.ad` | 广告列表 |
| `add` | GET/POST | `/api/m/tao.cms/admin.ad/add` | 添加广告 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.ad/edit` | 编辑广告 |
| `delete` | POST | `/api/m/tao.cms/admin.ad/delete` | 删除广告 |
| `modify` | POST | `/api/m/tao.cms/admin.ad/modify` | 快捷修改 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `status` | int | 状态筛选 |
| `begin_at` | string | 开始日期筛选 |
| `active` | bool | 传 `true` 查询当前活跃广告 |
| `tag` | string | 标签筛选 |

**add/edit POST 参数（白名单）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `begin_at` | string | 否 | 开始时间 |
| `end_at` | string | 否 | 结束时间 |
| `cover` | string | 否 | 封面图 |
| `title` | string | 否 | 标题 |
| `link` | string | 否 | 链接 |
| `kind` | int | 否 | 类型 |
| `at_index` | bool | 否 | 首页展示 |
| `at_list` | bool | 否 | 列表展示 |
| `at_page` | bool | 否 | 页面展示 |
| `at_banner` | bool | 否 | Banner 展示 |
| `tag` | string | 否 | 标签 |
| `sort` | int | 否 | 排序 |
| `remark` | string | 否 | 备注 |
| `gname` | string | 否 | 分组名 |

---

### 7.3 AlbumController — 图集管理

**路由：** `/api/m/tao.cms/admin.album`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsAlbum`  
**可修改字段：** `status`, `sort`, `remark` + `tag`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.album` | 图集列表 |
| `add` | GET/POST | `/api/m/tao.cms/admin.album/add` | 添加图集 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.album/edit` | 编辑图集 |
| `delete` | POST | `/api/m/tao.cms/admin.album/delete` | 删除图集 |
| `modify` | POST | `/api/m/tao.cms/admin.album/modify` | 快捷修改 |
| `preview` | GET | `/api/m/tao.cms/admin.album/preview` | 图集预览 |

**add/edit POST 参数（白名单）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `cover` | string | 否 | 封面图 |
| `title` | string | 否 | 标题 |
| `tag` | string | 否 | 标签 |
| `summary` | string | 否 | 摘要 |
| `image_ids` | string | 否 | 图片 ID 列表（逗号分隔） |

**edit/preview GET 响应：** 额外返回 `images` 字段（图片 URL 列表）

---

### 7.4 ArticleController — 文章管理

**路由：** `/api/m/tao.cms/admin.article`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsArticle`  
**可修改字段：** `status`, `sort`, `remark` + `top`（超管额外：`hits`, `hot`, `cstatus`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.article` | 文章列表 |
| `add` | GET/POST | `/api/m/tao.cms/admin.article/add` | 添加文章 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.article/edit` | 编辑文章 |
| `delete` | POST | `/api/m/tao.cms/admin.article/delete` | 删除文章 |
| `modify` | POST | `/api/m/tao.cms/admin.article/modify` | 快捷修改 |
| `cstatus` | POST | `/api/m/tao.cms/admin.article/cstatus` | 文章审核 |
| `preview` | GET | `/api/m/tao.cms/admin.article/preview` | 文章预览 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `cate_id` | int | 栏目 ID |
| `cstatus` | int | 审核状态 |

**add POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `cate_id` | int | 是 | 栏目 ID |
| `title` | string | 是 | 标题 |
| `content` | string | 是 | 内容 |
| `cover` | string | 否 | 封面图 |
| `keywords` | string | 否 | 关键词 |
| `summary` | string | 否 | 摘要 |
| `author` | string | 否 | 作者（默认"管理员"） |
| `hits` | int | 否 | 点击量 |
| `image_ids` | string | 否 | 图片 ID 列表 |

**add GET 响应：**
```json
{ "options": [{ "id": 1, "otitle": "新闻", "title": "新闻" }] }
```
返回栏目选项列表。

**edit GET 响应：** 额外返回 `images`(图片列表) 和 `content`(文章内容)

**cstatus POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int | 是 | 文章 ID |
| `cstatus` | int | 是 | 审核状态 |
| `cmessage` | string | 条件必填 | 不通过原因（拒绝时必填） |

**preview 响应：** 返回文章详情含 `images` 和 `content`

---

### 7.5 CategoryController — 栏目管理

**路由：** `/api/m/tao.cms/admin.category`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsCategory`  
**可修改字段：** `status`, `sort`, `remark` + `navbar`, `name`, `tag`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.category` | 栏目列表（树形） |
| `add` | GET/POST | `/api/m/tao.cms/admin.category/add` | 添加栏目 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.category/edit` | 编辑栏目 |
| `delete` | POST | `/api/m/tao.cms/admin.category/delete` | 删除栏目 |
| `modify` | POST | `/api/m/tao.cms/admin.category/modify` | 快捷修改 |

**index 响应：** 树形表格数据，排序 `pid ASC, sort DESC, id ASC`

**add POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `title` | string | 是 | 栏目名称 |
| `kind` | int | 是 | 栏目类型 |
| `pid` | int | 否 | 上级栏目 ID |
| `name` | string | 否 | 标识 |
| `tag` | string | 否 | 标签 |
| `summary` | string | 否 | 摘要 |
| `cover` | string | 否 | 封面图 |
| `navbar` | bool | 否 | 导航栏显示 |
| `sort` | int | 否 | 排序 |
| `status` | int | 否 | 状态 |
| `other` | string | 否 | 其他信息 |
| `image_ids` | string | 否 | 图片 ID 列表 |
| `content` | string | 条件必填 | 栏目内容（kind=列表时） |

**add GET 响应：**
```json
{ "pid": 0, "categoryList": [{ "id": 0, "pid": 0, "title": "一级栏目" }] }
```

**edit GET 响应：** 额外返回 `content`(栏目内容) 和 `images`(图片列表)

---

### 7.6 LinkController — 链接管理

**路由：** `/api/m/tao.cms/admin.link`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsLink`  
**可修改字段：** `status`, `sort`, `remark` + `tag`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.link` | 链接列表 |
| `add` | GET/POST | `/api/m/tao.cms/admin.link/add` | 添加链接 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.link/edit` | 编辑链接 |
| `delete` | POST | `/api/m/tao.cms/admin.link/delete` | 删除链接 |
| `modify` | POST | `/api/m/tao.cms/admin.link/modify` | 快捷修改 |

---

### 7.7 PageController — 单页管理

**路由：** `/api/m/tao.cms/admin.page`  
**基础类：** `BaseTaoA0CmsController`  
**模型：** `CmsPage`  
**查询字段：** `id`, `tag`, `name`, `title`, `sort`, `status`  
**可修改字段：** `sort`, `status`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.cms/admin.page` | 单页列表 |
| `add` | GET/POST | `/api/m/tao.cms/admin.page/add` | 添加单页 |
| `edit` | GET/POST | `/api/m/tao.cms/admin.page/edit` | 编辑单页 |
| `delete` | POST | `/api/m/tao.cms/admin.page/delete` | 删除单页 |
| `modify` | POST | `/api/m/tao.cms/admin.page/modify` | 快捷修改 |

**index 查询参数：**

| 参数 | 类型 | 说明 |
|---|---|---|
| `status` | int | 状态筛选 |
| `tag` | int | 标签筛选 |

**add/edit POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `title` | string | 是 | 标题 |
| `name` | string | 是 | 页面标识 |
| `content` | string | 是 | 页面内容 |
| `tag` | string | 否 | 标签 |
| `sort` | int | 否 | 排序 |

**注意：** `tag + name` 组合必须唯一，不允许重复。

---

### 7.8 HelperController — 图集辅助

**路由：** `/api/m/tao.cms/user.helper`  
**基础类：** `BaseTaoA0CmsController`  
**启用 Action：** `select`, `edit`  
**权限：** 需登录（`userActions = '*'`）

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `select` | GET | `/api/m/tao.cms/user.helper/select` | 图集图片选择 |
| `edit` | GET/POST | `/api/m/tao.cms/user.helper/edit` | 修改图片摘要 |

**edit POST 参数：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int(Query) | 是 | 图片 ID |
| `summary` | string(POST) | 是 | 图片摘要 |

**注意：** 只能修改自己上传的图片。

---

## 8. 应用控制器（A0/app）

### 8.1 InfoController — 应用信息

**路由：** `/api/m/tao.app/admin.info`  
**基础类：** `BaseController`  
**模型：** `AppInfo`  
**权限：** 超级管理员（`superAdminActions = '*'`）  
**可修改字段：** `status`, `sort`, `remark` + `title`

| Action | 方法 | 路径 | 说明 |
|---|---|---|---|
| `index` | GET | `/api/m/tao.app/admin.info` | 应用信息列表 |
| `add` | GET/POST | `/api/m/tao.app/admin.info/add` | 添加应用信息 |
| `edit` | GET/POST | `/api/m/tao.app/admin.info/edit` | 编辑应用信息 |
| `delete` | POST | `/api/m/tao.app/admin.info/delete` | 删除应用信息 |
| `modify` | POST | `/api/m/tao.app/admin.info/modify` | 快捷修改 |

**add/edit POST 参数（白名单）：**

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `tag` | string | 否 | 标签 |
| `title` | string | 否 | 标题 |
| `remark` | string | 否 | 备注 |

---

## 9. 通用 CRUD 参数速查

### 9.1 index 查询通用参数

| 参数 | 类型 | 默认值 | 说明 |
|---|---|---|---|
| `page` | int | 1 | 页码 |
| `limit` | int | 15 | 每页条数 |
| `reset` | int | - | 传 `1` 重置搜索条件 |
| `status` | int | 0 | 状态筛选（大部分控制器默认查 status=0） |

### 9.2 modify 通用参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int | 是 | 记录 ID |
| `field` | string | 是 | 字段名（必须在 `allowModifyFields` 范围内） |
| `value` | mixed | 否 | 新值 |

### 9.3 delete 通用参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| `id` | int/array | 是 | 记录 ID（支持数组批量删除） |

### 9.4 edit 通用参数

| 参数 | 位置 | 类型 | 必填 | 说明 |
|---|---|---|---|---|
| `id` | Query | int | 是 | 记录 ID |
| 其他字段 | POST | mixed | 否 | 表单数据 |

---

## 10. 小程序/API 端请求约定

小程序端接口通过 `?data=jsonbody` 参数标识 JSON Body 请求：

**请求格式：**
```http
POST /api/m/tao.open/auth/login?data=jsonbody
Content-Type: application/json

{ "account": "user@example.com", "password": "123456" }
```

**鉴权方式：**
```http
Authorization: {"token":"1.app.1700000000","t":1700000123,"sign":"md5签名"}
```

> 详细签名机制见 [tao_login.md](tao_login.md) 第 9 节

---

## 11. 错误码汇总

| code | 说明 |
|---|---|
| `0` | 成功 |
| `401` | 登录凭证格式错误 |
| `403` | 登录凭证已过期 |
| `500` | 一般性业务错误 |

**常见业务错误信息：**

| 错误信息 | 场景 |
|---|---|
| 记录不存在 | edit/delete/modify 时 ID 无效 |
| 该字段不允许修改 | modify 时 field 不在白名单 |
| 当前模型不存在此属性 | modify 时字段名不存在 |
| 必须使用 POST 方法 | 非 POST 请求访问 POST-only 接口 |
| 没有修改记录的权限 | 非管理员修改他人数据 |
| 不允许删除超级管理员 | 删除受保护的系统账号 |
