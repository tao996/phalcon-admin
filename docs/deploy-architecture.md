# 部署架构设计 — ReverseProxy + DockerNetwork 模式

> 设计日期：2025-07-07
> 最后更新：2025-07-08
> 状态：已实现（v2）

---

## 一、核心思想

**目标**：将重复的手动部署流程（SSH → 创建目录 → git clone → cp 配置 → 改端口 → 配 nginx）变为一条命令。

**手段**：
- 所有项目容器置于共享 Docker 网络 `phalcon-shared`，通过容器名相互寻址
- 流量统一经过 Router Nginx，按域名分发到各项目
- 部署工具通过 `phpseclib` 执行远程操作，本地渲染配置后上传
- **预览/执行两阶段**：先本地生成配置文件供检查，确认后再推送到远程

---

## 二、架构拓扑

### Docker Router 模式（新服务器）

```
宿主机（仅暴露 80/443）               Docker 内部网络
┌──────────────────────┐
│   Router Nginx       │← 80/443  用户访问
│   (Docker 容器)       │
└──────┬───────────────┘
       │ 共享网络: phalcon-shared
       │
  ┌────┼────┬────┬────┐
  │    │    │    │    │
  ▼    ▼    ▼    ▼    ▼
 ┌──┐ ┌──┐ ┌──┐ ┌──┐
 │A-│ │A-│ │A-│ │A-│ ...
 │n🐘│ │p🐘│ │my│ │re│
 │gx│ │hp│ │sl│ │ds│
 └──┘ └──┘ └──┘ └──┘
 项目 Alpha          项目 Beta
```

### 宿主机 Nginx 模式（已有 nginx 的服务器）

```
宿主机 Nginx（已运行）                Docker 内部网络
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
  ↑ 端口暴露到 host
  项目 Alpha
```

### 流量路径

```
用户访问 demo.example.com
  → Host:80 → Router（Docker 容器 或 宿主机 nginx）
  → 根据 server_name 匹配
  → proxy_pass http://demo-nginx:80   （Docker Router 模式）
    或 proxy_pass http://127.0.0.1:8071  （宿主机 Nginx 模式）
  → demo-nginx 处理静态文件 / 代理 PHP 到 demo-php:9000
```

---

## 三、目录结构

```
项目根目录/
├── deploy                          — PHP CLI 入口（12 个命令）
├── .env.example                    — 开发环境配置模板（含 {{VAR}} 占位符）
├── deploys/
│   ├── server.php                  — 服务器连接配置（实际使用）
│   ├── server.example.php          — 模板
│   ├── .cache/mode.txt             — Router 模式本地缓存（自动生成）
│   ├── src/                        — 部署引擎 PHP 代码
│   │   ├── helpers.php             — 辅助函数
│   │   ├── Config.php              — 配置加载 + 合并
│   │   ├── SSH.php                 — SSH 连接（基于 phpseclib v3 SFTP）
│   │   ├── TemplateRenderer.php    — 模板渲染（{{KEY}} 替换）
│   │   ├── GitHelper.php           — 远程 git 操作
│   │   ├── RouterManager.php       — Router Nginx 管理（环境检测、双模式）
│   │   ├── ProjectDeployer.php     — 项目部署编排
│   │   └── DbManager.php           — 数据库运维（隧道 / phpMyAdmin）
│   ├── template/                   — 配置模板
│   │   ├── .env.example            — 生产 .env 模板（Docker Compose 使用 ${VAR} 原生语法）
│   │   ├── docker-compose.yaml     — 无端口映射（Docker Router 模式）
│   │   ├── docker-compose.ports.yaml — 有端口暴露（宿主机模式，使用 ${VAR} 原生语法）
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
| 7 | Docker Router | `docker inspect -f '{{.State.Running}}' phalcon-router 2>/dev/null` | 输出 `true` 则表示已在运行 | 是否已初始化 |

### 模式判定逻辑（determineMode）

```
如果 Docker Router 容器已在运行           →  Docker Router 模式（复用已有）
否则如果 nginx 进程在运行 或 80 端口被占用  →  宿主机 Nginx 模式
否则                                       →  Docker Router 模式（新服务器）
```

### 检测报告示例输出

```
  → ━━━━━ 服务器环境检测报告 ━━━━━
  ℹ 系统:       CentOS Linux 7 (Core)
  ℹ Nginx:      已安装  (运行中)
  ℹ Nginx 配置: /etc/nginx/conf.d
  ℹ Certbot:    已安装
  ℹ 端口 80:    已被占用
  ℹ 端口 443:   已被占用
  ℹ Docker Router: 未运行

  → 推荐模式:   宿主机 Nginx
  ⚠ 使用 -y 参数执行安装：php deploy server:init -y
```

---

## 五、server:init -y 执行流程

`php deploy server:init -y` 先检测，然后根据模式执行对应的安装步骤。

### Docker Router 模式执行流程

```
1. 创建共享 Docker 网络
   docker network create phalcon-shared 2>/dev/null || echo 'network already exists'

2. 创建 Router 配置目录
   mkdir -p /root/router
   mkdir -p /etc/nginx-router/conf.d

3. 上传 Router 的 docker-compose.yaml（由 generateRouterCompose() 生成）

4. 启动 Router 容器
   cd /root/router && docker-compose up -d

完成。
```

### 宿主机 Nginx 模式执行流程

```
1. 创建共享 Docker 网络
   docker network create phalcon-shared 2>/dev/null || echo 'network already exists'

2. 确认宿主机 nginx 配置目录存在
   mkdir -p /etc/nginx/conf.d

3. 验证 nginx 配置是否有效
   nginx -t

4. 如果 certbot 未安装，给出提示

5. 后续 init 项目时：
   为每个项目分配一个端口（从 8071 起）
   项目的 nginx 容器暴露该端口到 host
   生成 server block 写入 /etc/nginx/conf.d/<project>.conf
   proxy_pass http://127.0.0.1:<port>;

完成。
```

### 两种模式的关键差异一览

| 步骤 | Docker Router 模式 | 宿主机 Nginx 模式 |
|------|-------------------|-------------------|
| 共享网络 | ✅ 创建 | ✅ 创建 |
| Docker Router 容器 | ✅ 部署 nginx 容器监听 80/443 | ❌ 不部署，复用宿主机 nginx |
| 项目 proxy_pass | `http://<project>-nginx:80`（Docker DNS） | `http://127.0.0.1:<port>`（宿主机地址） |
| 项目 docker-compose | 无端口映射 | 暴露 `${NGINX_PORT}` 到 host |
| nginx 重载 | `docker exec phalcon-router nginx -s reload` | `nginx -s reload` 或 `systemctl reload nginx` |
| SSL 证书 | 需手动配置或容器内 certbot | 复用系统 certbot（已安装时） |

---

## 六、配置模板渲染

### 数据流演变（v2）

```
旧（v1）：
  TemplateRenderer → {{VAR}} → docker-compose.ports.yaml、config.php 等

新（v2）：
  TemplateRenderer → {{VAR}} → .env（填充项目实际值）
                                ↓
  Docker Compose 原生 ${VAR}  ← docker-compose.ports.yaml（静态）
  PHP env()                   ← config.php（继承 services.docker.example.php）
```

### 预览/执行两阶段（v2 新增）

```
php deploy app:init <项目>           # 预览模式（无 -y）
  └─ 本地渲染所有配置文件到 deploys/projects/<name>/
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
| `deploys/template/.env.example` | `<project>/.env` | 生产环境变量（8 个，无 dev 端口变量） |
| `deploys/template/docker-compose.ports.yaml` | `<project>/docker-compose.ports.yaml` | 宿主机模式（使用 `${VAR}` 原生语法） |
| 项目根 `docker-compose.yaml`（fallback） | `<project>/docker-compose.yaml` | Docker Router 模式（原样上传） |
| `deploys/template/nginx/default.conf` | `<project>/docker/nginx/sites/default.conf` | 项目内部 nginx 配置 |
| `deploys/template/php/php.ini` | `<project>/docker/php/php.ini` | PHP 生产配置（同步自 `docker/php/php.prod.ini`） |
| `deploys/template/mysql/my.cnf` | `<project>/docker/mysql/my.cnf` | MySQL 配置 |
| `deploys/template/config.php.template` | `<project>/src/config/config.php` | 应用配置（app.title、jwt secret 等） |

### 变量来源

```
$vars = [
    // 基础变量
    'APP_NAME' => 项目名,
    'NGINX_PORT' => 自动分配端口,
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

### 服务器连接配置 `deploys/server.php`

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
    'project' => [              // 所有项目的默认值，各项目可覆盖
        'repo' => 'git@github.com:user/phalcon-admin.git',
        'branch' => 'main',
    ],
    'docker' => [
        'network' => 'phalcon-shared',
    ],
    'router' => [
        'containerName' => 'phalcon-router',
        'configDir' => '/etc/nginx-router/conf.d',
        'composePath' => '/root/router',
    ],
    'env' => [
        'TZ' => 'Asia/Shanghai',
        'REDIS_PASSWORD' => '123456',
        'MYSQL_PASSWORD' => '123456',
        'MYSQL_USER' => 'admin',
    ],
];
```

### 项目配置 `deploys/projects/<name>/server.php`

```php
<?php
return [
    'project' => [
        'name' => 'myapp',
        'path' => '/root/projects/myapp',
        'modules' => [
            'demo' => 'git@github.com:user/module-demo.git',
        ],
        // repo/branch 继承自 server.php，可不填
        // 'nginxPort' => 8071,
    ],
    'domains' => [
        'myapp.example.com',
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

`Config::getMerged()` 通过 `array_merge_deep()`（定义在 `src/tao996/Phax/function.php`）合并 `server.php` + 项目 `server.php`。

```php
// server.php 配置
['project' => ['repo' => 'git@...com:main.git', 'branch' => 'main']]

// 项目 server.php 配置（只覆盖 name, path）
['project' => ['name' => 'myapp', 'path' => '/root/projects/myapp']]

// 合并结果（repo, branch 继承自 server.php）
['project' => ['name' => 'myapp', 'path' => '/root/projects/myapp',
               'repo' => 'git@...com:main.git', 'branch' => 'main']]
```

项目配置中定义的同名键会覆盖 `server.php` 的默认值。

---

## 八、init 项目完整流程

### 预览模式（无 -y，v2 新增）

```
01. 读取 deploys/server.php + projects/<name>/server.php
02. 检测 Router 模式（本地缓存 → 默认 host_nginx，不连远程）
03. 渲染配置文件到本地 deploys/projects/<name>/
04. 输出：请检查后执行 php deploy app:init <name> -y
```

### 执行模式（-y）

```
01. 读取 deploys/server.php + projects/<name>/server.php
02. 检测 Router 模式（本地缓存 → 远程检测）
03. SSH 连接远程服务器
04. mkdir -p <project.path>
05. git clone <repo> <path> --branch <branch>
06. git clone 子模块到 src/App/Modules/<name>
07. 配置文件上传：
    a) 有本地文件（预览生成）→ 读取并 SFTP 上传
    b) 无本地文件 → 模板渲染后上传
08. docker-compose -f <模板文件> up -d
09. 生成 nginx server block → 上传到 Router 配置目录 → reload nginx
10. 执行 afterInit 钩子（如 php artisan migration）
```

---

## 九、CLI 命令集

### 命令列表

| 命令 | 功能说明 | 新增于 |
|------|---------|--------|
| `php deploy --help` | 显示帮助 | v1 |
| `php deploy server:init` | 检测服务器环境，打印报告后退出 | v1 |
| `php deploy server:init -y` | 检测 + 自动选择模式并执行安装 | v1 |
| `php deploy server:init -y mode=host_nginx` | 强制宿主机 Nginx 模式 | v1 |
| `php deploy app:init <project>` | 预览（无 -y）或完整部署（加 -y） | v1→v2 增强 |
| `php deploy app:upgrade <project>` | 更新已有项目（git pull + 重启） | v1 |
| `php deploy app:dc:restart <project>` | 启动/重启 Docker 容器（首次拉取镜像） | v2 |
| `php deploy app:dc:status <project>` | 查看项目容器状态 | v2 |
| `php deploy app:dc:log <project>` | 查看全部容器日志 | v2 |
| `php deploy app:dc:log:php <project>` | 查看 PHP 容器日志 | v2 |
| `php deploy app:push <project>` | 推送本地配置文件到远程（覆盖已有） | v2 |
| `php deploy app:nginx:add <project>` | 将项目域名添加到 Router | v1 |
| `php deploy app:nginx:remove <project>` | 从 Router 移除项目域名 | v1 |
| `php deploy nginx:reload` | 验证语法后重载 Nginx（全局） | v2 |
| `php deploy nginx:log:error` | 查看 Nginx 错误日志（--save 下载） | v2 |
| `php deploy nginx:log:access` | 查看 Nginx 访问日志（--save 下载） | v2 |
| `php deploy db:proxy <project>` | SSH 隧道转发：本地 → 远程 MySQL | v1 |
| `php deploy db:pma <project>` | 部署临时 phpMyAdmin | v1 |
| `php deploy db:pma-rm <project>` | 删除临时 phpMyAdmin | v1 |

### 参数

| 参数 | 作用于 | 说明 |
|------|--------|------|
| `-y` | `server:init`, `app:init` | 自动执行；`server:init` 默认只检测，`app:init` 默认预览 |
| `env=prod` | 所有命令 | 选择服务器配置 `server.{env}.php` |
| `mode=host_nginx` | `server:init -y`, `app:init` | 强制宿主机模式 |
| `port=8071` | `app:init` | 手动指定项目 nginx 端口（宿主机模式） |
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

1. 读取 `deploys/server.php` 中的 SSH 连接信息（host、user、keyFile）
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

运行方式：`php src/vendor/bin/phpunit -c deploys/phpunit.xml`

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
cp deploys/server.example.php deploys/server.php
# 编辑 deploys/server.php 填入真实服务器信息

# 2. 配置项目
cp deploys/projects/.example/server.php deploys/projects/yihe/server.php
# 编辑 deploys/projects/yihe/server.php 填入项目名、路径、域名

# 3. 检测服务器环境
php deploy server:init

# 4. 如果报告满意，执行安装
php deploy server:init -y

# 5. 预览项目配置
php deploy app:init yihe
# 检查 deploys/projects/yihe/ 下的配置文件

# 6. 确认无误后部署
php deploy app:init yihe -y
```

### 更新配置

```bash
# 修改本地配置后推送
php deploy app:init yihe                # 重新预览生成
# 手动编辑 deploys/projects/yihe/* 中的文件
php deploy app:push yihe         # 仅推送配置到远程
php deploy app:dc:restart yihe           # 重启容器使配置生效
```

### 日常更新

```bash
php deploy app:upgrade yihe
```

### Nginx 操作

```bash
# 添加域名到 Router
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
