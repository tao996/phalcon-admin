# 项目架构与运行流程

## 入口：`public/index.php`

```
请求 → index.php
        1. define('PATH_ROOT', dirname(__DIR__) . '/')     // 指向 src/
        2. 检测 phalcon 扩展，未加载则切 phar 回退
        3. require bootstrap/app.php                        // 初始化
        4. Application::runWeb()                            // 运行 HTTP 请求
```

---

## 启动流程（bootstrap/app.php）

```
bootstrap/app.php
  └─ tao996/index.php                    // 常量定义 + 自动加载 + 全局函数
       ├─ PATH_ROOT, PATH_CONFIG, PATH_APP, PATH_STORAGE ...
       ├── define('IS_PHP_FPM', isset($_SERVER['HTTP_HOST']))
       ├── const IS_WEB = IS_PHP_FPM || IS_WORKER_WEB
       ├── Phalcon\Autoload\Loader       // PSR-4: App\ → App/, Phax\ → Phax/
       └── Phax\function.php             // env() 等全局函数
  └─ new Application(PATH_ROOT)          // 检查目录存在
  └─ $app->autoloadServices()
       ├── Env::load(PATH_ROOT . '.env')  // 加载 .env 到 $_ENV / putenv（纯 PHP 解析）
       ├── define('IS_DEBUG', ...)
       └── DiService::with($di)           // 注册全局 DI 服务
            ├── config()                  // 加载 config.php → Phalcon\Config\Config
            │    ├── 设置时区 app.timezone
            │    ├── 注册额外命名空间 app.loader.namespaces
            │    └── 引入额外文件 app.loader.includes
            ├── logger()                  // 日志
            ├── crypt()                   // 加密
            ├── modelsMetadata()          // 模型元数据缓存
            ├── profiler()                // SQL 性能分析器
            └── security()               // 安全组件
  └── IS_DEBUG → error_reporting(E_ALL)
  └── set_error_handler("prettyError")    // 将 PHP Error/Warning 转为 ErrorException
  └── Application::runWeb() 内 catch(\Throwable)          // 覆盖 PHP Error + Exception
```

---

## HTTP 请求生命周期（Application::runWeb）

```
Application::runWeb($requestURL)
  │
  ├── DiService::with($di)              // 注册请求级服务
  │    ├── db()                         // 数据库连接（支持读写分离）
  │    ├── pdo()                        // PDO 适配器
  │    ├── redis()                      // Redis 缓存
  │    ├── cache()                      // 缓存适配器
  │    ├── flash()                      // 消息提示
  │    ├── session()                    // 会话管理（文件/Redis）
  │    ├── cookies()                    // Cookie 加密
  │    ├── url()                        // URL 生成
  │    ├── router()                     // 路由
  │    ├── view()                       // 视图引擎（PHP/Volt）
  │    └── application()                // Phalcon Application
  │
  ├── routeWith($requestURL, $di)       // 路由解析与分发
  │    │
  │    ├── 读取 config app.defaultApp   // 获取默认命名空间 + 视图目录
  │    │    ├── defaultNamespace → options
  │    │    └── defaultViewpath  → options
  │    │
  │    ├── new Route($requestURL)       // URL 预处理
  │    │    ├── 路由映射 web.php         // /login → /m/tao/auth/index
  │    │    ├── Router::pathMatch()      // 分解 URL: language/api/module/project/path
  │    │    └── 计算 origin()            // 当前域名
  │    │
  │    ├── Router::analysisWithURL()    // 路由分析
  │    │    ├── 语言前缀 /cn/ /en/
  │    │    ├── API 前缀 /api/
  │    │    ├── 模块 /m/{module}/{controller}/{action}
  │    │    ├── 项目 /p/{project}/{controller}/{action}
  │    │    ├── 单应用（无前缀）/{controller}/{action}
  │    │    ├── 子模块（点号分隔）/m/tao.wechat/
  │    │    └── 子目录（点号分隔）/m/tao/sub.c1/
  │    │
  │    ├── router->add(route, paths)     // 注册到 Phalcon Router
  │    ├── registerModules()             // 多模块注册
  │    ├── useImplicitView(false)        // API 请求禁止自动渲染视图
  │    │
  │    └── application->handle()         // Phalcon 分发
  │         ├── Dispatcher → Controller  // 实例化控制器
  │         │    ├── initialize()        // 初始化（Response → RBAC → Base）
  │         │    ├── rbacInitialize()     // 权限检查
  │         │    └── {action}Action()    // 执行动作
  │         └── View → render()          // 渲染视图
  │
  └── 响应输出
       ├── API: setJsonContent()         // JSON 响应
       ├── Web: view->render()           // HTML 响应
       └── 捕获异常: catch(\Throwable)   // 进入 handleException（统一日志 + 响应）
            ├── Logger::exception($e)    // 完整堆栈记录
            └── AppErrorResponse         // API → JSON, Web → 错误页模板
```

---

## 配置驱动（src/config/config.php）

config.php 加载 services.example.php 作为基础配置，然后覆写开发环境配置：

```
services.example.php（模板）
    │  merge
    ▼
config.php（本地覆盖）
    │
    ▼
Phalcon\Config\Config 对象
    │
    ▼
Config::path('xxx') 读取
```

### 关键配置项

```
app
├── title             应用标题
├── url               站点 URL（用于生成链接）
├── https             是否 HTTPS
├── timezone          时区
├── locale            语言（cn/en）
├── jwt               JWT 配置（hmac/secret/expire/subject）
├── error             错误处理类 App\Http\AppErrorResponse
├── defaultApp        单应用默认命名空间和视图目录（可替换 App\Http\Controllers）
├── cdn_locate        CDN 域名
├── loader            额外命名空间和文件加载
├── demo              是否演示系统
├── superAdmin        超级管理员 ID 列表
├── test              测试环境（tokens）
├── sites             域名 → 项目映射
└── default           默认项目

session
├── driver            redis / stream / memcached / noop
├── cookie_lifetime   Cookie 有效期
└── stores.redis      连接配置

database
├── driver            mysql / pgsql / sqlite
├── host / port / dbname / username / password
├── prefix            表前缀
└── log               SQL 日志

redis
├── host / port / auth / index
├── prefix            全局 key 前缀 _phx_
└── persistent        长连接

metadata              Phalcon 模型元数据驱动
crypt                 加密密钥
flash                 消息提示驱动（Session）
cookie                加密密钥
```

---

## 路由类型一览

| URL 示例 | 路由类型 | Namespace |
|---|---|---|
| `/` | 默认首页 | `config app.defaultApp.namespace`（默认 `App\Http\Controllers`） |
| `/c1/a1` | 单应用 | `config app.defaultApp.namespace`（默认 `App\Http\Controllers`） |
| `/sub.c1/a1` | 单应用+子目录 | 默认 namespace + `\sub` |
| `/m/tao/user/index` | 多模块 | `App\Modules\tao\Controllers` |
| `/m/tao.sub/user/index` | 多模块+子模块 | `App\Modules\tao\A0\sub\Controllers` |
| `/m/tao/sub.c1/a1` | 多模块+子目录 | `App\Modules\tao\Controllers\sub` |
| `/p/family/home/index` | 项目（前端） | `App\Projects\family\Controllers` |
| `/api/...` | API 请求（不渲染视图） | 同上 + 隐式视图关闭 |
| `/cn/...` | 多语言前缀 | 同上 + paths 索引 +1 |

---

## 错误处理链

```
应用程序异常 / PHP Error / Warning
    │
    ├── set_error_handler          // 将 PHP Error/Warning/Notice
    │    └── throw ErrorException  // 转为异常，进入统一处理
    │
    ▼
catch (LocationException)         → 302 跳转
catch (BlankException)            → 输出纯文本
catch (\Throwable)                // 覆盖 Exception + Error
    │
    └─ Logger::exception($e)       // 完整堆栈写入日志
    │    └─ [类名] 消息
    │       file.php(line)
    │       完整 trace
    │
    └─ Application::handleException()
         │
         ├─ 读取 config app.error → App\Http\AppErrorResponse
         │
         ├─ Dispatcher\Exception → AppErrorResponse::notFound()
         │    ├─ API → {code:404, msg:"接口不存在"}
         │    └─ Web → 渲染 views/error/not_found.phtml
         │
         └─ 其他异常 → AppErrorResponse::exception()
              ├─ API → {code:500, msg, trace(调试)}
              │    生产: "系统繁忙，请稍后再试"
              │    调试: 原始消息 + 文件 + 行号 + 完整 trace
              └─ Web → 渲染 views/error/exception.phtml
                   生产: 友好提示 + 参考编号
                   调试: 类名/消息/文件/行号/请求 URL/完整堆栈
```

---

## 控制器继承链

```
Phalcon\Mvc\Controller
    └─ Phax\Mvc\Controller              // 基础功能：DI 访问、beforeExecuteRoute 等
         └─ BaseResponseController       // 响应类型检测、MyMvcHelper 初始化
              └─ BaseRbacController       // RBAC 权限检查、isLogin()
                   └─ BaseController      // 自动 CRUD：index/add/edit/delete/modify
                        ├─ yihe/CustomerController    // 子模块控制器
                        ├─ tao/UserController
                        └─ ...
```

**BaseController 内置 CRUD 方法：**

| 方法 | 路由 | 说明 |
|---|---|---|
| `indexAction` | `GET /m/{module}/{controller}` | 列表 + API（自动识别） |
| `addAction` | `GET+POST /m/{module}/{controller}/add` | 添加记录 |
| `editAction` | `GET+POST /m/{module}/{controller}/edit` | 编辑记录 |
| `deleteAction` | `POST /m/{module}/{controller}/delete` | 批量删除 |
| `modifyAction` | `POST /m/{module}/{controller}/modify` | 快捷修改单个字段 |

---

## 视图系统

```
views/{theme}/
  ├── index.{phtml|volt}         ← 布局文件（mainView）
  └── {controller}/
       ├── index.{phtml|volt}    ← 列表页
       ├── add.phtml              ← 添加表单
       └── edit.phtml             ← 编辑表单
```

- 主题支持：`theme` 属性可为空或目录名
- 自动回退：模块/项目未找到自己的布局文件时，使用 PHAR 内置布局
- API 请求：`$application->useImplicitView(false)` 禁止视图渲染

---

## 服务容器（DI）

**全局 DI**（`DiService::defaultContainer()`）：

- Web 模式：`Phalcon\Di\FactoryDefault`
- CLI 模式：`Phalcon\Di\FactoryDefault\Cli`

**请求级服务**在 `runWeb()` 中注册：

| 服务 | 依赖 | 说明 |
|---|---|---|
| `config` | Config | 全局配置对象 |
| `db` | Phalcon\Db\Adapter\Pdo | 数据库（单例，支持主从） |
| `session` | Phalcon\Session\Manager | 会话 |
| `redis` | Redis | 缓存/会话存储 |
| `router` | Phalcon\Mvc\Router | 路由 |
| `view` | Phalcon\Mvc\View | 视图 |
| `dispatcher` | Phalcon\Mvc\Dispatcher | 分发器 |
| `url` | Phalcon\Mvc\Url | URL 生成 |
| `cookies` | Phalcon\Http\Response\Cookies | Cookie 加密 |
| `flash` | Phalcon\Flash\Session | 消息提示 |
