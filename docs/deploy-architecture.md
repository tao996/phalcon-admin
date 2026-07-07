# 部署架构设计 — ReverseProxy + DockerNetwork 模式

> 设计日期：2025-07-07
> 最后更新：2025-07-07
> 状态：已实现（v1）

---

## 一、核心思想

**目标**：将重复的手动部署流程（SSH → 创建目录 → git clone → cp 配置 → 改端口 → 配 nginx）变为一条命令。

**手段**：
- 所有项目容器置于共享 Docker 网络 `phalcon-shared`，通过容器名相互寻址
- 流量统一经过 Router Nginx，按域名分发到各项目
- 部署工具通过 `phpseclib` 执行远程操作，本地渲染配置后上传

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
├── deploy                          — PHP CLI 入口（7 个命令）
├── deploys/
│   ├── server.example.php          — 服务器连接配置模板
│   ├── src/                        — 部署引擎 PHP 代码
│   │   ├── helpers.php             — 辅助函数
│   │   ├── Config.php              — 配置加载 + 合并
│   │   ├── SSH.php                 — SSH 连接（基于 phpseclib v3 SFTP）
│   │   ├── TemplateRenderer.php    — 模板渲染（{{KEY}} 替换）
│   │   ├── GitHelper.php           — 远程 git 操作
│   │   ├── RouterManager.php       — Router Nginx 管理（环境检测、双模式）
│   │   └── ProjectDeployer.php     — 项目部署编排
│   ├── template/                   — 配置模板
│   │   ├── .env
│   │   ├── docker-compose.yaml     — 无端口映射（Docker Router 模式）
│   │   ├── docker-compose.ports.yaml — 有端口暴露（宿主机 Nginx 模式）
│   │   ├── nginx/default.conf      — 项目内部 nginx 站点配置
│   │   ├── php/php.ini             — 生产环境 PHP 配置
│   │   ├── mysql/my.cnf
│   │   ├── config.php.template     — 应用主配置模板（.template 避免 IDE 误解析）
│   │   └── server.php              — 旧版模板保留
│   ├── projects/                   — 项目配置
│   │   ├── .example/server.php     — 项目配置模板
│   │   └── yihe/server.php         — 自定义模块示例
│   ├── tests/                      — 单元测试
│   │   ├── bootstrap.php
│   │   ├── helpersTest.php         — 17 tests
│   │   ├── ConfigTest.php          — 12 tests
│   │   ├── TemplateRendererTest.php — 16 tests
│   │   └── fixtures/
│   └── phpunit.xml
└── .gitignore                      — 已排除 server.php 和 projects 内容
```

---

## 四、环境检测（init:router 不带 -y）

`php deploy init:router`（不加 `-y`）只检测不执行。检测后打印报告并退出。

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
  ⚠ 使用 -y 参数执行安装：php deploy init:router -y
```

---

## 五、init:router -y 执行流程

`php deploy init:router -y` 先检测，然后根据模式执行对应的安装步骤。

### Docker Router 模式执行流程

```
1. 创建共享 Docker 网络
   docker network create phalcon-shared 2>/dev/null || echo 'network already exists'

2. 创建 Router 配置目录
   mkdir -p /root/router
   mkdir -p /etc/nginx-router/conf.d

3. 上传 Router 的 docker-compose.yaml（由 generateRouterCompose() 生成）
   ┌──────────────────────────────────────────┐
   │version: '3.5'                            │
   │services:                                  │
   │  router:                                  │
   │    image: nginx:stable-alpine              │
   │    container_name: phalcon-router          │
   │    ports:                                  │
   │      - "80:80"                             │
   │      - "443:443"                           │
   │    volumes:                                │
   │      - /etc/nginx-router/conf.d:/etc/nginx/conf.d │
   │      - /etc/nginx-router/ssl:/etc/nginx/ssl       │
   │    networks:                               │
   │      - phalcon-shared                      │
   │    restart: always                         │
   │networks:                                   │
   │  phalcon-shared:                           │
   │    external: true                          │
   └──────────────────────────────────────────┘

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
   提示: certbot 未安装，SSL 证书需手动配置
   安装: apt install certbot python3-certbot-nginx

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
| 项目 docker-compose | 无端口映射 | 暴露 `{{NGINX_PORT}}` 到 host |
| nginx 重载 | `docker exec phalcon-router nginx -s reload` | `nginx -s reload` 或 `systemctl reload nginx` |
| SSL 证书 | 需手动配置或容器内 certbot | 复用系统 certbot（已安装时） |

---

## 六、配置模板渲染（init 项目时）

`php deploy init <project>` 时，`ProjectDeployer::renderConfigs()` 在**本地**渲染以下模板并通过 SFTP 上传：

| 模板文件（deploys/template/） | 生成到服务器的文件 | 变量来源 |
|---------|-------------------|---------|
| `.env` | `<project>/.env` | `env` 字段 + 默认值 |
| `docker-compose.yaml` | `<project>/docker-compose.yaml` | Docker Router 模式使用 |
| `docker-compose.ports.yaml` | `<project>/docker-compose.ports.yaml` | 宿主机模式使用，含 `{{NGINX_PORT}}` |
| `nginx/default.conf` | `<project>/docker/nginx/sites/default.conf` | 项目 nginx 内部配置 |
| `php/php.ini` | `<project>/docker/php/php.ini` | `{{TZ}}` |
| `mysql/my.cnf` | `<project>/docker/mysql/my.cnf` | 通用 |
| `config.php.template` | `<project>/src/config/config.php` | `config` 字段 |

渲染后上传命令（通过 phpseclib SFTP）：

```bash
# 每个文件通过 SFTP put 上传到远程对应路径
# 然后执行：
docker-compose -f <模板文件> up -d     # 启动容器
```

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
        'app.title' => 'My App',
        'app.origin' => 'https://myapp.example.com/',
        'app.jwt.secret' => 'change-this-secret',
        'app.https' => true,
        'app.demo' => false,
        'app.superAdmin' => [1],
    ],
    'hooks' => [
        'afterInit' => [
            'shell:php artisan migration',
        ],
    ],
];
```

### 配置合并规则

`Config::getMerged()` 通过 `array_merge_deep()` 合并 `server.php` + 项目 `server.php`。

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

```
01. 读取 deploys/server.php + projects/<name>/server.php
02. 检测 Router 模式（本地缓存 → 远程 Docker 容器检查 → 回退自动检测）
03. SSH 连接远程服务器
04. mkdir -p <project.path>
05. git clone <repo> <path> --branch <branch>
06. git clone 子模块到 src/App/Modules/<name>
07. 渲染配置文件（本地 TemplateRenderer）：
    - .env
    - docker-compose.yaml 或 docker-compose.ports.yaml（根据模式）
    - docker/nginx/sites/default.conf
    - docker/php/php.ini
    - docker/mysql/my.cnf
    - src/config/config.php.template → config.php
08. SFTP 上传渲染后的配置文件到服务器
09. docker-compose -f <模板文件> up -d
10. 生成 nginx server block → 上传到 Router 配置目录 → reload nginx
11. 执行 afterInit 钩子（如 php artisan migration）
```

---

## 九、CLI 命令集

### 命令列表

| 命令 | 功能说明 |
|------|---------|
| `php deploy --help` | 显示帮助 |
| `php deploy init:router` | 检测服务器环境（nginx/certbot/端口/OS），打印报告后退出 |
| `php deploy init:router -y` | 检测 + 自动选择模式并执行安装 |
| `php deploy init:router -y mode=host_nginx` | 强制宿主机 Nginx 模式 |
| `php deploy init <project>` | 首次部署项目（git clone + 配置 + docker-compose + router） |
| `php deploy upgrade <project>` | 更新已有项目（git pull + 重启） |
| `php deploy nginx:add <project>` | 将 project 的域名添加到 Router |
| `php deploy nginx:remove <project>` | 从 Router 移除 project 的域名 |
| `php deploy status <project>` | 查看项目容器运行状态 |
| `php deploy db:proxy <project>` | SSH 隧道转发：本地端口 → 远程项目的 MySQL |
| `php deploy db:pma <project>` | 部署临时 phpMyAdmin，通过宿主机端口访问 |
| `php deploy db:pma-rm <project>` | 删除临时 phpMyAdmin 容器 |

### 参数

| 参数 | 作用于 | 说明 |
|------|--------|------|
| `-y` | `init:router` | 自动执行安装；不加则只检测 |
| `env=prod` | 所有命令 | 选择服务器配置 `server.{env}.php` |
| `mode=host_nginx` | `init:router -y`、`init`、`upgrade` | 强制宿主机模式 |
| `port=8071` | `init`、`upgrade` | 手动指定项目 nginx 端口（宿主机模式） |
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

# 输出示例：
#   http://服务器IP:13307
#   用户名: admin
#   密码:   (从 projects/yihe/server.php 的 env.MYSQL_PASSWORD 获取)

# 自定义端口
php deploy db:pma yihe host=13308

# 使用完后清理
php deploy db:pma-rm yihe
```

**安全：** 临时容器（`--rm`），端口仅在使用期间开放；用完 `db:pma-rm` 删除。容器基于 `phalcon-shared` 以外的项目内网，不暴露到其他项目。

---

## 十一、单元测试

运行方式：`php src/vendor/bin/phpunit -c deploys/phpunit.xml`

| 测试文件 | 测试数 | 覆盖内容 |
|---------|--------|---------|
| `helpersTest.php` | 17 | `array_get`（嵌套/默认/缺失）、`safe_name`、`array_merge_deep` 多层级合并 |
| `ConfigTest.php` | 12 | 配置加载、合并、各 getter 方法 |
| `TemplateRendererTest.php` | 16 | 单文件渲染、`renderToFile`、`renderDir` 目录渲染、跳过 `_` 文件、子目录结构保持、边界情况 |

未测（需真实 SSH 连接）：`SSH.php`、`GitHelper.php`、`RouterManager.php` 远程部分、`ProjectDeployer.php` 编排部分。这些在 `php deploy init <project>` 实际运行时验证。

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
php deploy init:router

# 4. 如果报告满意，执行安装
php deploy init:router -y

# 5. 部署项目
php deploy init yihe
```

### 日常更新

```bash
php deploy upgrade yihe
```

### 新增域名

```bash
# 在 deploys/projects/yihe/server.php 的 domains 中添加域名后
php deploy nginx:add yihe
```

### 数据库操作

```bash
# SSH 隧道连接 MySQL
php deploy db:proxy yihe
# 在另一个终端登录：mysql -h127.0.0.1 -P13306 -uadmin -p

# 临时 phpMyAdmin
php deploy db:pma yihe
# 访问 http://服务器IP:13307

# 清理 phpMyAdmin
php deploy db:pma-rm yihe
```
