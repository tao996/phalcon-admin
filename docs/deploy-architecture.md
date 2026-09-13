# 部署架构设计 — 宿主机 Nginx 反向代理模式

> 设计日期：2025-07-07
> 最后更新：2026-09-12
> 状态：已实现（v3：代码同步统一为 `sync.items` 条目模型（git/bundle/ftp 可组合）；Docker Router 模式已移除，统一宿主机 Nginx 反代）

---

## 一、核心思想

**目标**：将重复的手动部署流程（SSH → 创建目录 → git clone → cp 配置 → 改端口 → 配 nginx）变为一条命令。

**手段**：
- 各项目容器在自身 backend 网络内运行，nginx 绑定 `127.0.0.1:<nginxPort>`
- 宿主机 Nginx 按 server_name 反向代理到 `127.0.0.1:<nginxPort>`
- 部署工具通过 `phpseclib` 执行远程操作，本地渲染配置后上传
- **代码同步统一模型（sync.items）**：每个同步目标独立声明方式，三种方式可任意组合：
  - `git` — 远程 `git clone/pull`（需要远程能访问仓库）
  - `bundle` — 本地 `git bundle` 直传（远程无需 GitHub 凭据，见第十三节）
  - `ftp` — SFTP 增量直传不被 git 跟踪的目录（见第十五节）
- **预览/执行两阶段**：先本地生成配置文件供检查，确认后再推送到远程

---

## 二、架构拓扑

### 宿主机 Nginx 反向代理

```
宿主机（仅暴露 80/443）               Docker 内部网络
┌────────────────────┐
│  nginx + certbot   │← 80/443  用户访问
│  (宿主机原生)        │
└──────┬─────────────┘
    proxy_pass 127.0.0.1:<port>
       │
  ┌────┼────┬────┬────┐
  │    │    │    │    │
  ▼    ▼    ▼    ▼    ▼
 ┌──┐ ┌──┐ ┌──┐ ┌──┐
 │A-│ │A-│ │A-│ │A-│ ...
 │n🐘│ │p🐘│ │my│ │re│
 │gx│ │hp│ │sl│ │ds│
 └──┘ └──┘ └──┘ └──┘
  ↑ nginx 绑定 127.0.0.1:<port>（不对公网开放）
  项目 Alpha          项目 Beta
```

### 流量路径

```
用户访问 demo.example.com
  → Host:80 → 宿主机 nginx
  → 根据 server_name 匹配 /etc/nginx/conf.d/<project>.conf
  → proxy_pass http://127.0.0.1:<nginxPort>
  → demo-nginx 处理静态文件 / 代理 PHP 到 demo-php:9000
```

---

## 三、目录结构

```
项目根目录/
├── deploy                          — PHP CLI 入口（12 个命令）
├── .env.example                    — 开发环境配置模板（含 {{VAR}} 占位符）
├── deploy/
│   ├── server.php                  — 服务器连接配置（实际使用）
│   ├── server.example.php          — 模板
│   ├── .cache/                     — 本地缓存（server-<ip>_<port>.json 服务器级 / <项目>.json 项目级）
│   ├── src/                        — 部署引擎 PHP 代码
│   │   ├── helpers.php             — 辅助函数
│   │   ├── Config.php              — 配置加载 + 合并
│   │   ├── SSH.php                 — SSH 连接（基于 phpseclib v3 SFTP）
│   │   ├── TemplateRenderer.php    — 模板渲染（{{KEY}} 替换）
│   │   ├── GitHelper.php           — 远程 git 操作（github 模式）
│   │   ├── LocalGitSync.php        — 本地 bundle 同步（local 模式）
│   │   ├── RouterManager.php       — 宿主机 Nginx 管理（环境检测、域名 server block）
│   │   ├── ProjectDeployer.php     — 项目部署编排
│   │   └── DbManager.php           — 数据库运维（隧道 / phpMyAdmin）
│   ├── template/                   — 配置模板
│   │   ├── .env.example            — 生产 .env 模板（Docker Compose 使用 ${VAR} 原生语法）
│   │   ├── docker-compose.yaml     — 统一模板（nginx 绑定 127.0.0.1:${NGINX_PORT}，使用 ${VAR} 原生语法）
│   │   ├── nginx/default.conf      — 项目内部 nginx 站点配置
│   │   ├── php/php.ini             — 生产环境 PHP 配置（同步自 docker/php/php.prod.ini）
│   │   ├── mysql/my.cnf
│   │   └── config.php.template     — 应用主配置模板（{{CONFIG_OVERRIDES}} 注入）
│   ├── projects/                   — 项目配置
│   │   ├── .example/server.php     — 项目配置模板
│   │   └── yihe/                   — 示例项目
│   │       ├── server.php          — 项目配置
│   │       ├── .env                — 本地预览生成的 .env（init 无 -y）
│   │       └── ...                 — 预览生成的配置文件
│   └── tests/                      — 单元测试
└── docker/                         — 本地开发环境
    └── php/php.prod.ini            — 生产 PHP 配置源文件
```

---

## 四、环境检测（server:init 不带 -y）

`php deploy server:init`（不加 `-y`）只检测不执行。检测后打印报告并退出。

### 检测项及执行命令

| # | 检测项 | 实际执行的 SSH 命令 | 判定依据 | 作用 |
|---|--------|-------------------|---------|------|
| 1 | 操作系统 | `. /etc/os-release 2>/dev/null && echo "$PRETTY_NAME"` | 输出 `PRETTY_NAME` | 显示发行版信息 |
| 2 | Nginx 进程 | `ps aux 2>/dev/null \| grep -v grep \| grep -q ' nginx'` | `echo YES` 或 `echo NO` | 判断 nginx 是否在运行 |
| 3 | Nginx 命令 | `command -v nginx >/dev/null 2>&1` | `echo YES` 或 `echo NO` | 判断 nginx 命令是否在 PATH 中 |
| 4 | Certbot | `command -v certbot >/dev/null 2>&1` | `echo YES` 或 `echo NO` | 判断 certbot 是否可用 |
| 5 | 端口 80 | `ss -tln 2>/dev/null \| grep -q '\.80 '` → 失败则 fallback `netstat -tln \| grep -q '\.80 '` | `echo 'in_use'` 或 `echo 'free'` | 判断 80 是否被占用 |
| 6 | 端口 443 | 同上，换为 443 | 同上 | 判断 443 是否被占用 |
| 7 | Docker / Compose | `command -v docker`、`docker compose version` / `docker-compose --version` | 命令可用 | 检测 compose 命令名（v1/v2）并缓存 |

### 检测报告示例输出

```
  → ━━━━━ 服务器环境检测报告 ━━━━━
  ℹ 系统:       CentOS Linux 7 (Core)
  ℹ Nginx:      已安装  (运行中)
  ℹ Nginx 配置: /etc/nginx/conf.d
  ℹ Certbot:    已安装
  ℹ 端口 80:    已被占用
  ℹ 端口 443:   已被占用
  ℹ Docker:      已安装
  ℹ Docker Compose: docker compose

  ⚠ 使用 -y 参数执行安装：php deploy server:init -y
```

---

## 五、server:init -y 执行流程

`php deploy server:init -y` 先检测，然后执行初始化：

```
1. 确认宿主机 nginx 配置目录存在
   mkdir -p /etc/nginx/conf.d

2. 验证 nginx 配置是否有效
   nginx -t

3. 如果 certbot 未安装，给出提示

4. 后续 init 项目时：
   nginx 端口取项目 server.php 的 project.nginxPort（默认 8071）
   项目的 nginx 容器绑定 127.0.0.1:<port>（不对公网开放）
   生成 server block 写入 /etc/nginx/conf.d/<project>.conf
   proxy_pass http://127.0.0.1:<port>;

完成。
```

> Docker Router 模式（phalcon-router 容器统一 80/443）已于 v3 移除；
> 若服务器上还残留 phalcon-router 容器，请手动停止并移除，域名统一走宿主机 nginx。

---

## 六、配置模板渲染

### 数据流演变（v2）

```
旧（v1）：
  TemplateRenderer → {{VAR}} → docker-compose.yaml、config.php 等

新（v2）：
  TemplateRenderer → {{VAR}} → .env（填充项目实际值）
                                ↓
  Docker Compose 原生 ${VAR}  ← docker-compose.yaml（静态）
  PHP env()                   ← config.php（继承 services.docker.example.php）
```

### 预览/执行两阶段（v2 新增）

```
php deploy app:init <项目>           # 预览模式（无 -y）
  └─ 本地渲染所有配置文件到 deploy/projects/<name>/
  └─ 不连接远程服务器
  └─ 输出：请检查后执行 php deploy app:init <项目> -y

php deploy app:init <项目> -y        # 执行模式
  └─ 优先读取本地已生成的配置文件 → SFTP 上传
  └─ 无本地文件时回退到模板渲染
  └─ 完整部署流程（git clone + docker up + router）
```

### 模板文件清单

| 模板源 | 生成为 | 说明 |
|--------|--------|------|
| `deploy/template/.env.example` | `<project>/.env` | 生产环境变量（8 个，无 dev 端口变量） |
| `deploy/template/docker-compose.yaml` | `<project>/docker-compose.yaml` | 统一模板（nginx 绑定 `127.0.0.1:${NGINX_PORT}`，由宿主机 nginx 反代，不对公网开放） |
| `deploy/template/nginx/default.conf` | `<project>/docker/nginx/sites/default.conf` | 项目内部 nginx 配置 |
| `deploy/template/php/php.ini` | `<project>/docker/php/php.ini` | PHP 生产配置（同步自 `docker/php/php.prod.ini`） |
| `deploy/template/mysql/my.cnf` | `<project>/docker/mysql/my.cnf` | MySQL 配置 |
| `deploy/template/config.php.template` | `<project>/src/config/config.php` | 应用配置（app.title、jwt secret 等） |

### 变量来源

```
$vars = [
    // 基础变量
    'APP_NAME' => 项目名,
    'NGINX_PORT' => server.php 的 project.nginxPort（默认 8071）,
    'DATA_PATH_HOST' => 项目路径 + '/docker/storage',
    'TZ' => 'Asia/Shanghai',
    'MYSQL_USER' => 项目名（可被 server.php env 覆盖）,

    // server.php env 覆盖（array_merge）
    'MYSQL_DATABASE', 'MYSQL_PASSWORD', 'REDIS_PASSWORD' 等,

    // server.php config 覆盖（深层合并到 config.php）
    'CONFIG_OVERRIDES' => server.php 的 config 段 → 嵌套数组直接注入
]
```

### config.php 深层合并（v2）

config.php.template 使用 `{{CONFIG_OVERRIDES}}` 注入 server.php 的 config 段，
通过 `array_merge_deep()`（定义在 `src/tao996/Phax/function.php`）递归合并到
`services.docker.example.php` 的默认配置上：

```php
$data = include __DIR__ . '/services.docker.example.php';
$data = array_merge_deep($data, [
    'app' => [
        'title' => '义和环保',            // 来自 config.app.title
        'origin' => 'https://yihe.gu19.cn/',
        'jwt' => ['secret' => '...'],    // 点键自动展开
        'https' => true,
        'demo' => false,
        'superAdmin' => [1],
    ],
]);
return $data;
```

不再需要手动维护 `{{APP_TITLE}}` → `JWT_SECRET` 之类的模板变量映射。

### 自定义 Docker 镜像

通过 `server.php` 的 `docker.images` 段覆盖 compose 模板中的镜像地址：

```php
'docker' => [
    'images' => [
        'php' => 'registry.example.com/phalcon:5.13.0',
        'nginx' => 'registry.example.com/nginx:stable-alpine',
    ],
],
```

compose 模板使用 `${VAR:-默认值}` 语法，不设置时自动回退到默认镜像。

---

## 七、配置文件结构

### 服务器连接配置 `deploy/server.php`

```php
<?php
return [
    'ssh' => [
        'host' => '1.2.3.4',
        'port' => 22,
        'user' => 'root',
        'password' => '',       // 与 keyFile 二选一
        // 'keyFile' => '~/.ssh/id_rsa',
        // 'keyPassphrase' => '',
    ],
    // 代码同步方式为项目级配置（sync.items，见下），全局 server.php 不再定义
    'env' => [
        'TZ' => 'Asia/Shanghai',
        'REDIS_PASSWORD' => '123456',
        'MYSQL_PASSWORD' => '123456',
        'MYSQL_USER' => 'admin',
    ],
];
```

### 项目配置 `deploy/projects/<name>/server.php`

```php
<?php
return [
    // 可选：覆盖 deploy/server.php 的连接（项目独立服务器，或本地 bundle 同步时）
    // 'ssh' => ['host' => '192.168.56.120', 'port' => 22, 'user' => 'root', 'password' => '123456'],
    'project' => [
        'name' => 'myapp',
        'path' => '/root/projects/myapp',
        // 'nginxPort' => 8071,
    ],
    // 代码同步方式（按声明顺序逐项执行，三种方式可任意组合）：
    //   git    — 远程 clone/pull（需要远程能访问仓库），repo 必填
    //   bundle — 本地仓库打包直传（远程无需 GitHub 凭据），本地源目录 = 本地仓库根 + path
    //   ftp    — SFTP 增量直传（只增改不删除），适合不被 git 跟踪的目录
    // path 为相对项目根的目录，省略 path 表示主仓库（ftp 不支持主仓库）
    'sync' => [
        // 'target' => 'remote',  // remote（默认，SSH/SFTP 到服务器）| filesystem（写本地目录，见第十四节）
        'items' => [
            ['method' => 'git', 'repo' => 'git@github.com:user/phalcon-admin.git', 'branch' => 'main'],
            ['method' => 'git', 'path' => 'src/App/Modules/yihe', 'repo' => 'git@github.com:user/yihe.git'],
        ],
    ],
    'domains' => [
        'myapp.example.com',
    ],
    // nginx server block 状态（ssl 由 nginx:ssl 命令成功后自动维护）
    'nginx' => [
        'ssl' => false,
    ],
    'env' => [
        'APP_NAME' => 'myapp',
        'MYSQL_DATABASE' => 'myapp_db',
    ],
    'config' => [
        'app' => [
            'title' => 'My App',
            'origin' => 'https://myapp.example.com/',
            'demo' => false,
            'superAdmin' => [1],
        ],
    ],
    'hooks' => [
        'afterInit' => [
            'shell:php artisan migration',
        ],
    ],
];
```

### 配置合并规则

`Config::getMerged()` 通过 `array_merge_deep()`（定义在 `src/tao996/Phax/function.php`）合并 `server.php` + 项目 `server.php`。项目配置中定义的同名键会覆盖 `server.php` 的默认值（如项目级 `ssh` 覆盖默认连接）。

代码同步完全由项目配置的 `sync.items` 声明，`Config::getSyncItems()` 将每项归一化为
`['method' => git|bundle|ftp, 'path' => string, 'repo' => string, 'branch' => string（默认 main）, 'excludes' => string[]（ftp 方式：不上传的相对路径前缀）]`。

---

## 八、init 项目完整流程

### 预览模式（无 -y，v2 新增）

```
01. 读取 deploy/server.php + projects/<name>/server.php
02. 渲染配置文件到本地 deploy/projects/<name>/
03. 输出：请检查后执行 php deploy app:init <name> -y
```

### 执行模式（-y）

```
01. 读取 deploy/server.php + projects/<name>/server.php
02. SSH 连接远程服务器
04. mkdir -p <project.path>
05. 按 sync.items 逐项同步代码（git / bundle / ftp）
06. 配置文件上传：
    a) 有本地文件（预览生成）→ 读取并 SFTP 上传
    b) 无本地文件 → 模板渲染后上传
07. docker-compose -f docker-compose.yaml up -d
08. 生成 nginx server block → 上传到 /etc/nginx/conf.d/<project>.conf → reload nginx
09. 执行 afterInit 钩子（如 php artisan migration）
```

---

## 九、CLI 命令集

### 命令列表

| 命令 | 功能说明 | 新增于 |
|------|---------|--------|
| `php deploy --help` | 显示帮助 | v1 |
| `php deploy server:init` | 检测服务器环境，打印报告后退出 | v1 |
| `php deploy server:init -y` | 检测 + 自动选择模式并执行安装 | v1 |
| `php deploy app:init <project>` | 预览（无 -y）或完整部署（加 -y） | v1→v2 增强 |
| `php deploy app:upgrade <project>` | 更新已有项目（按 sync.items 同步代码，`method=` 可只执行某种方式；-y 时同时更新配置并重启） | v1 |
| `php deploy app:dc:restart <project>` | 启动/重启 Docker 容器（首次拉取镜像） | v2 |
| `php deploy app:dc:status <project>` | 查看项目容器状态 | v2 |
| `php deploy app:dc:log <project>` | 查看全部容器日志 | v2 |
| `php deploy app:dc:log:php <project>` | 查看 PHP 容器日志 | v2 |
| `php deploy app:push <project>` | 推送本地配置文件到远程（覆盖已有） | v2 |
| `php deploy app:<project> git:ssh [-T]` | 为 git@ 开头的 git 同步项生成 deploy key（-T 验证认证） | v3 |
| `php deploy app:nginx:add <project>` | 生成本地 nginx 配置（事实源）并上传到 /etc/nginx/conf.d/ | v1→v3 |
| `php deploy app:nginx:remove <project>` | 从 Router 移除项目域名 | v1 |
| `php deploy nginx:reload` | 验证语法后重载 Nginx（全局） | v2 |
| `php deploy nginx:log:error` | 查看 Nginx 错误日志（--save 下载） | v2 |
| `php deploy nginx:log:access` | 查看 Nginx 访问日志（--save 下载） | v2 |
| `php deploy db:backup <project>` | 立即备份数据库（默认存服务器，`download=1` 同时下载本地） | v3 |
| `php deploy db:backup:list <project>` | 查看服务器备份列表 | v3 |
| `php deploy db:backup:get <project> [文件]` | 下载备份到本地（缺省最新） | v3 |
| `php deploy db:backup:cron <project> -y [time=03:00] [keep=7]` | 安装/更新每日定时备份（服务器 crontab） | v3 |
| `php deploy db:backup:cron-rm <project>` | 移除定时备份（保留已生成备份） | v3 |
| `php deploy db:proxy <project>` | SSH 隧道转发：本地 → 远程 MySQL | v1 |
| `php deploy db:pma <project>` | 部署临时 phpMyAdmin | v1 |
| `php deploy db:pma-rm <project>` | 删除临时 phpMyAdmin | v1 |

### 参数

| 参数 | 作用于 | 说明 |
|------|--------|------|
| `-y` | `server:init`, `app:init` | 自动执行；`server:init` 默认只检测，`app:init` 默认预览 |
| `env=prod` | 所有命令 | 选择服务器配置 `server.{env}.php` |
| `method=git\|bundle\|ftp` | `app:upgrade` | 只执行该方式的同步项（值与 sync.items 的 method 一致，无匹配时报错退出） |
| `full=1` | `app:upgrade` | ftp 条目忽略增量清单，强制全量上传 |
| `local=13306` | `db:proxy` | SSH 隧道本地监听端口 |
| `host=13307` | `db:pma` | phpMyAdmin 宿主机暴露端口 |

---

## 十、数据库运维

### SSH 隧道（方案 A）

`php deploy db:proxy <project>` 通过系统 `ssh -L` 命令建立加密隧道，将本地端口转发到远程项目的 MySQL 容器。

```
本地                           服务器
mysql -h127.0.0.1 -P13306
       ↑ SSH 加密隧道
       └────────── yihe-mysql:3306
```

**执行流程：**

1. 读取 `deploy/server.php` 中的 SSH 连接信息（host、user、keyFile）
2. 查找系统 SSH 二进制（Git Bash → Windows OpenSSH → /usr/bin/ssh）
3. 执行 `ssh -L 127.0.0.1:13306:yihe-mysql:3306 -N user@host -p 22 -i <key>`
4. 在前台保持连接，按 Ctrl+C 关闭

**使用示例：**

```bash
# 默认端口 13306
php deploy db:proxy yihe

# 在另一个终端连接 MySQL
mysql -h127.0.0.1 -P13306 -uadmin -p

# 导入 SQL 文件
mysql -h127.0.0.1 -P13306 -uadmin -p yihe_db < dump.sql

# 自定义本地端口
php deploy db:proxy yihe local=13308
```

**安全：** SSH 协议加密传输，MySQL 不暴露到公网，用完即关。

---

### 临时 phpMyAdmin（方案 D）

`php deploy db:pma <project>` 在服务器上部署一个临时的 phpMyAdmin 容器，通过宿主机端口访问 Web 界面。

```
你的浏览器                   服务器
http://服务器IP:13307
  ↓ Router / 直连
  └── docker:yihe-pma
        │ PMA_HOST=mysql
        └── yihe-mysql:3306
```

**执行流程：**

1. SSH 连接到服务器
2. 查找项目的 Docker 网络（`<project>_backend`）
3. `docker run -d --rm --name yihe-pma --network yihe_backend -p 13307:80 -e PMA_HOST=mysql phpmyadmin/phpmyadmin`
4. 打印访问地址和登录凭据

**使用示例：**

```bash
# 部署（默认端口 13307）
php deploy db:pma yihe

# 使用完后清理
php deploy db:pma-rm yihe
```

---

## 十一、单元测试

运行方式：`php src/vendor/bin/phpunit -c deploy/phpunit.xml`

| 测试文件 | 测试数 | 覆盖内容 |
|---------|--------|---------|
| `helpersTest.php` | 17 | `array_get`（嵌套/默认/缺失）、`safe_name`、`array_merge_deep` 多层级合并 |
| `ConfigTest.php` | 12 | 配置加载、合并、各 getter 方法 |
| `TemplateRendererTest.php` | 16 | 单文件渲染、`renderToFile`、`renderDir` 目录渲染、跳过 `_` 文件、子目录结构保持、边界情况 |

未测（需真实 SSH 连接）：`SSH.php`、`GitHelper.php`、`RouterManager.php` 远程部分、`ProjectDeployer.php` 编排部分。这些在 `php deploy app:init <project>` 实际运行时验证。

---

## 十二、使用步骤

### 首次搭建

```bash
# 1. 配置服务器连接
cp deploy/server.example.php deploy/server.php
# 编辑 deploy/server.php 填入真实服务器信息

# 2. 配置项目
cp deploy/projects/.example/server.php deploy/projects/yihe/server.php
# 编辑 deploy/projects/yihe/server.php 填入项目名、路径、域名

# 3. 检测服务器环境
php deploy server:init

# 4. 如果报告满意，执行安装
php deploy server:init -y

# 5. 预览项目配置
php deploy app:init yihe
# 检查 deploy/projects/yihe/ 下的配置文件

# 6. 确认无误后部署
php deploy app:init yihe -y
```

### 更新配置

```bash
# 修改本地配置后推送
php deploy app:init yihe                # 重新预览生成
# 手动编辑 deploy/projects/yihe/* 中的文件
php deploy app:push yihe         # 仅推送配置到远程
php deploy app:dc:restart yihe           # 重启容器使配置生效
```

### 日常更新

```bash
php deploy app:upgrade yihe
```

### Nginx 操作

```bash
# 生成本地 nginx 配置并上传到远程 /etc/nginx/conf.d/<项目>.conf
php deploy app:nginx:add yihe

# 全局重载 Nginx（先验证语法）
# 查看日志
php deploy app:dc:log yihe              # 查看全部容器日志
php deploy app:dc:log:php yihe          # 查看 PHP 容器日志
php deploy nginx:log:error              # 查看 Nginx 错误日志
php deploy nginx:log:access             # 查看 Nginx 访问日志
```

### 数据库操作

```bash
# SSH 隧道连接 MySQL
php deploy db:proxy yihe

# 临时 phpMyAdmin
php deploy db:pma yihe

# 清理 phpMyAdmin
php deploy db:pma-rm yihe
```

---

## 十三、本地 bundle 同步（sync.items 中 method=bundle）

> v2 引入，v3 改为 `sync.items` 条目。用于让远程服务器**不持有 GitHub 凭据**。

### 背景

远程 `git clone/pull`（method=git）需要服务器配置 deploy key 访问 GitHub。若把服务器密钥加到 GitHub **账号级**，一旦服务器被攻破，账号下所有仓库都会暴露。`method=bundle` 改为由**本地开发机**（本来就有 GitHub 权限 + 服务器 SSH 权限）做中转，服务器只接收代码、完全不接触 GitHub。改造后账号级密钥可直接撤销。

### 配置

```php
// deploy/projects/<name>/server.php
'sync' => [
    'items' => [
        // 主仓库（省略 path）
        ['method' => 'bundle', 'branch' => 'main'],
        // 任意子目录（本地源目录 = 本地仓库根 + path，需为独立 git 仓库）
        // ['method' => 'bundle', 'path' => 'src/App/Modules/yihe', 'branch' => 'main'],
    ],
],
```

项目级 `ssh` 会合并覆盖 `deploy/server.php` 的默认连接，因此无需 `env=` 指定服务器文件。

### 流程

```
本地
  1. git bundle create <tmp>.bundle <branch>
  2. SFTP 上传到服务器 /tmp/deploy-*.bundle
服务器
  3. 已有仓库: git fetch <bundle> <ref> && git reset --hard FETCH_HEAD
     空目录:   git init && git fetch <bundle> <ref> && git checkout -b <branch> FETCH_HEAD
  4. 删除临时 bundle
之后配置上传 / docker up / router 步骤与 git 方式完全一致
```

每个 `method=bundle` 条目各打包一次（主仓库 + 各子目录）。

### 浅克隆处理

`git bundle` 在浅克隆（存在 `.git/shallow`）下会生成“看似完整、实际缺对象”的坏包，远程 fetch 报 `did not send all necessary objects`。`LocalGitSync` 检测到浅克隆时，改用 HEAD 的 tree 经 `git commit-tree` 造一个无父提交再打包（快照 bundle）：

- 自包含，远程 fresh clone 可直接 fetch；
- 无需网络、**不改动本地仓库**；
- 远程历史在浅克隆下为单提交快照，不影响部署与 `reset`（非浅克隆仍打包真实分支历史）。

### 生成文件不受影响

`.env`、`docker-compose.yaml`、`docker/nginx|php|mysql/*`、`src/config/config.php` 等均被 `.gitignore` 排除，`git reset --hard` 不会覆盖它们，配置仍走 SFTP 通道上传。

### 命令示例

项目配置已含 `ssh` + `sync.items` 时，直接执行即可，无需 `env=`：

```bash
# 预览 / 部署
php admin app:vbox init
php admin app:vbox init -y

# 更新代码（bundle 条目自动走本地打包直传）
php admin app:vbox upgrade
```

### 安全说明

- 服务器端无需任何 GitHub 凭据，可将账号级/仓库级 deploy key 从服务器移除后撤销。
- `deploy/server*.php` 含连接凭据，已加入 `.gitignore`（仅保留 `server.example.php`）。

---

## 十四、本地文件系统同步（sync.target=filesystem）

> v2 引入，v3 改为 `sync.items` 条目。把代码 + 生成配置**镜像到本机目录**，不经 SSH/SFTP、不启动 docker。

### 用途

在 bundle 同步的基础上，`sync.target=filesystem` 让目标变成一台本地路径（`project.path` 为本地绝对路径），用于本地镜像/离线副本/无服务器环境的代码下发。

### 配置

```php
// deploy/projects/<name>/server.php
return [
    'project' => [
        'name' => 'phalcon-admin-test',
        'path' => 'D:/demo/code/phpProjects/phalcon-admin-test', // 本地目标目录
        'nginxPort' => 8071,
    ],
    'sync' => [
        'target' => 'filesystem',
        'items' => [
            ['method' => 'bundle', 'branch' => 'main'],
            // ftp 条目在 filesystem 目标下退化为本地目录复制（只增改不删除）
            // ['method' => 'ftp', 'path' => 'src/App/Projects/boyu'],
        ],
    ],
    // 无需 ssh；env / config 与其他项目一致
];
```

### 流程

```
1. 本地生成快照 bundle（浅克隆同样安全）— 每个 method=bundle 条目各一次
2. git -C <target> init/fetch/reset（首次自动创建目录）
3. method=ftp 条目直接复制本地目录到 <target>（mtime+size 比较，只增改不删除）
4. 生成配置写入 <target>：.env、docker-compose.yaml、
   docker/nginx/sites/default.conf、docker/php/php.ini、docker/mysql/my.cnf、src/config/config.php
（不执行 docker up / router / hooks；method=git 条目不支持 filesystem 目标）
```

### 命令

```bash
php admin app:phalcon-admin-test init       # 预览配置
php admin app:phalcon-admin-test init -y    # 同步代码 + 生成配置
php admin app:phalcon-admin-test upgrade     # 仅同步代码
php admin app:phalcon-admin-test upgrade -y  # 同步代码 + 生成配置
```

### 说明

- 生成文件均被目标仓库的 `.gitignore` 排除，`git status` 保持干净。
- 目标目录历史与远程一致（浅克隆下为单提交快照）。
- 缓存指纹按目标路径隔离（`filesystem:<path>`）。

---

## 十五、SFTP 目录直传（sync.items 中 method=ftp）

> v2 新增。同步**不被 git 跟踪**的目录（如 `src/App/Projects/*`，被主仓库 `.gitignore` 排除，bundle 同步不会带上）。

### 配置

```php
// deploy/projects/<name>/server.php — sync.items 中声明
'sync' => [
    'items' => [
        // 可配多个；excludes 为相对 path 的路径前缀，命中的目录/文件不上传
        ['method' => 'ftp', 'path' => 'src/App/Projects/boyu',
         'excludes' => ['views/assets', 'storage', '.git']],   // 可省略
    ],
],
```

### 用法

```bash
php admin app:<项目> upgrade                     # 同步全部 sync.items（ftp 条目增量，跳过无变化文件）
php admin app:<项目> upgrade method=ftp          # 只执行 ftp 条目（SFTP 增量直传）
php admin app:<项目> upgrade method=ftp full=1   # 忽略增量清单，强制全量上传
php admin app:<项目> upgrade method=bundle       # 只执行 bundle 条目（只拉主仓库代码）
```

- `method=` 的值与 sync.items 的 `method` 一致（git|bundle|ftp），无匹配条目时报错退出
- `method=` 只过滤同步代码这一步；带 `-y` 时配置更新 + 容器重启照常执行
- `init -y` / `upgrade`（不过滤）会自动执行 ftp 条目

### 行为

- 目录上传到 `<project.path>/<相对目录>`，如 `/data/phalcon-test/src/App/Projects/boyu`
- **只增改不删除**：本地删除的文件不删除远程对应文件
- 增量策略：记录每个目录**最后一次成功同步的开始时间**（`lastSync`），下次只上传 `mtime >= lastSync` 的文件；某次有上传失败则不更新该目录的 `lastSync`，下次整个目录重传
- lastSync 存于项目缓存 `deploy/.cache/<project>.json`（含服务器指纹，项目连接目标变更时自动失效）
- 注意：新拷入但 mtime 早于 lastSync 的文件会被漏传，需要时用 `full=1` 强制全量（excludes 仍然生效）

---

## 十六、Deploy Key 管理（git:ssh）

> 新增于 v3。为 sync.items 中 `method=git` 且 repo 以 `git@` 开头的条目在服务器上生成 deploy key（远程 clone 私有仓库需要）。`https://` 仓库走匿名拉取，不需要 key。

### 用法

```bash
php admin app:<项目> git:ssh       # 生成密钥 + 更新 ~/.ssh/config + 输出公钥与添加指引
php admin app:<项目> git:ssh -T    # 逐仓库验证认证（公钥需已添加到 Deploy keys）
```

### 行为

- 无符合条件的同步项时提示"没有需要配置"并退出
- **每仓库一把 ed25519 密钥**：`~/.ssh/deploy/<owner>_<repo>`（已存在则跳过生成，幂等）
- `~/.ssh/config` 使用 `# BEGIN/END deploy-managed` 托管块，按 host 累积 `IdentityFile`（含 `IdentitiesOnly`、`StrictHostKeyChecking accept-new`），托管块外的用户配置不动；多项目共用服务器时自动合并、不互相覆盖
- 生成后输出每个仓库的公钥，提示添加到 **Settings → Deploy keys**；添加后用 `-T` 验证（成功输出 `Hi owner/repo!`）

---

## 十六点五、nginx 配置本地事实源

> v3 起，宿主机 nginx server block 以本地文件为事实源：`deploy/projects/<项目>/nginx/<项目>.conf`
> （与远程 `/etc/nginx/conf.d/<项目>.conf` 同名对应）。

- `app:<项目> init`（预览）在文件**不存在时**生成该文件（domains + project.nginxPort + nginx.ssl 渲染）；
  **已存在则保留人工内容不覆盖**，`init -y` / `upgrade` 直接上传本地现有内容
- 需按当前配置重新生成时：`php admin app:<项目> nginx:add force=1`（或删除本地文件）
- `init -y` / `upgrade` / `nginx:add` 统一为"上传本地文件 → reload"，不再远程凭空生成
- `nginx:ssl` 成功后自动更新本地文件为含 443 的版本，并把项目配置 `nginx.ssl` 置为 true
  （该键由命令维护，人工不要手改；旧项目配置缺少 `'nginx' => ['ssl' => false]` 结构时需手动补）
- `nginx:remove` 只删远程配置，本地文件保留作为记录

---

## 十七、数据库备份（db:backup）

> 新增于 v3。备份远程项目的 MySQL（Docker 容器），支持立即备份与服务器 crontab 定时备份。

### 保存目录

| 位置 | 路径 |
|------|------|
| 服务器 | `<project.path>/docker/storage/backup/mysql/<项目>_<库>_<时间>.sql.gz` |
| 本地（下载时） | `deploy/backups/<项目>/`（已加入 .gitignore） |

### 备份方式

```
docker exec <项目>-mysql sh -c 'mysqldump --single-transaction --routines --triggers --events ...'
  | gzip > <备份文件>
```

- 凭据取自容器自身环境变量（`$MYSQL_USER/$MYSQL_PASSWORD/$MYSQL_DATABASE`），不出现在命令行
- 仅导出项目库（env.MYSQL_DATABASE）
- 上传前校验产物非空，失败即报错退出

### 命令

```bash
php admin app:<项目> db:backup                   # 立即备份，存服务器
php admin app:<项目> db:backup download=1        # 立即备份并下载到本地
php admin app:<项目> db:backup:list              # 查看服务器备份列表
php admin app:<项目> db:backup:get               # 下载最新一份到本地
php admin app:<项目> db:backup:get <文件名>       # 下载指定备份
php admin app:<项目> db:backup:cron              # 预览将写入的 crontab 条目
php admin app:<项目> db:backup:cron -y           # 安装定时备份（默认每天 03:00，保留 7 天）
php admin app:<项目> db:backup:cron -y time=04:30 keep=14   # 自定义时间与保留天数
php admin app:<项目> db:backup:cron-rm           # 移除定时备份（保留已生成的备份文件）
```

### 定时备份实现

- 写入服务器 crontab，带标记块幂等更新（多项目互不干扰，其它 crontab 条目不动）：

```
# BEGIN deploy-backup <项目>
0 3 * * * <mysqldump | gzip> && find <备份目录> -name '*.sql.gz' -mtime +7 -delete
# END deploy-backup <项目>
```

- 过期清理在 cron 内完成（`find -mtime +keep -delete`，按天数保留）
- 安装时确保 cron 服务在运行；服务器未安装 crontab 命令时提示 `apt install cron`
- 备份与数据同盘，重要项目建议定期 `db:backup:get` 拉回本地
- 恢复（手动）：`gunzip < dump.sql.gz | docker exec -i <项目>-mysql mysql -u<user> -p <库名>`
