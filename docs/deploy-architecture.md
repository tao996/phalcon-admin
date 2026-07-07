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
 │A-│ │A-│ │B-│ │B-│ ...
 │n🐘│ │p🐘│ │n🐘│ │p🐘│
 │gx│ │hp│ │gx│ │hp│
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
│   │   ├── SSH.php                 — SSH 连接（基于 phpseclib）
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
│   │   ├── config.php              — 应用主配置
│   │   └── server.php              — 旧版模板保留
│   ├── projects/                   — 项目配置
│   │   ├── .example/server.php     — 项目配置模板
│   │   ├── demo/server.php
│   │   ├── tao/server.php
│   │   └── yihe/server.php
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

## 四、双模式设计

`init:router` 会自动检测服务器环境并在两种模式间选择。

### 检测项目

| 检测项 | 方法 | 影响 |
|--------|------|------|
| Nginx 是否安装 | `command -v nginx` | 决定模式选择 |
| Nginx 是否运行 | `nginx -t` | 判断 80/443 是否可用 |
| Certbot 是否安装 | `command -v certbot` | SSL 策略提示 |
| 端口 80/443 状态 | `ss -tlnp` / `netstat` | 判断端口是否被占用 |
| Docker Router 容器 | `docker inspect` | 检测是否已初始化过 |

### 模式对比

| 维度 | Docker Router 模式 | 宿主机 Nginx 模式 |
|------|-------------------|-------------------|
| 触发条件 | 新服务器，80/443 空闲 | 已有 nginx，端口占用 |
| docker-compose 模板 | `docker-compose.yaml`（无 ports） | `docker-compose.ports.yaml`（含 `{{NGINX_PORT}}`） |
| proxy_pass target | `project-nginx:80`（Docker DNS） | `127.0.0.1:<port>`（宿主机地址） |
| nginx 重载方式 | `docker exec phalcon-router nginx -s reload` | `nginx -s reload` 或 `systemctl reload nginx` |
| SSL 管理 | Docker certbot 或手动 | 复用系统已有 certbot |
| 端口管理 | 无需 | 每项目一个端口（自动分配，可从 8071 起） |

### 工作流

```
php deploy init:router                # 检测环境 → 打印报告 → 提示加 -y
php deploy init:router -y             # 检测 → 自动选择模式 → 执行安装
php deploy init:router -y mode=host_nginx  # 强制宿主机模式
```

不加 `-y` 时安全只读，适合先预览再决定。

---

## 五、CLI 命令集

| 命令 | 功能 |
|------|------|
| `php deploy --help` | 显示帮助 |
| `php deploy init:router` | 检测服务器环境（默认只报告，加 `-y` 才执行） |
| `php deploy init:router -y` | 执行初始化（自动选择模式） |
| `php deploy init <project>` | 首次部署项目 |
| `php deploy upgrade <project>` | 更新已有项目（git pull + 重启） |
| `php deploy nginx:add <project>` | 为项目添加域名到 Router |
| `php deploy nginx:remove <project>` | 从 Router 移除项目域名 |
| `php deploy status <project>` | 查看项目容器状态 |

参数：
- `-y` — 自动执行（`init:router` 默认只检测）
- `env=prod` — 选择服务器配置（`server.prod.php`）
- `mode=host_nginx` — 强制宿主机模式
- `port=8071` — 手动指定 nginx 端口（宿主机模式）

---

## 六、配置文件结构

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
        'repo' => 'https://github.com/tao996/phalcon-admin.git',
        'branch' => 'main',
        'path' => '/root/projects/myapp',
        'modules' => [
            'demo' => 'git@github.com:user/module-demo.git',
        ],
        // 'nginxPort' => 8071,   // 宿主机模式端口（可选）
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

---

## 七、配置模板渲染

模板文件位于 `deploys/template/`，使用 `{{KEY}}` 占位符。

每个 .example 文件对应一个部署时生成的正式文件：

| 模板文件 | 生成目标 | 变量来源 |
|---------|---------|---------|
| `.env` | `<project>/.env` | `env` 字段 + 默认值 |
| `docker-compose.yaml` | `<project>/docker-compose.yaml` | 通用（Docker Router 模式） |
| `docker-compose.ports.yaml` | `<project>/docker-compose.ports.yaml` | + `{{NGINX_PORT}}`（宿主机模式） |
| `nginx/default.conf` | `<project>/docker/nginx/sites/default.conf` | 通用 |
| `php/php.ini` | `<project>/docker/php/php.ini` | `{{TZ}}` |
| `mysql/my.cnf` | `<project>/docker/mysql/my.cnf` | 通用 |
| `config.php` | `<project>/src/config/config.php` | `config` 字段 |

渲染流程在本地完成，然后通过 SCP 上传到服务器。不依赖远程服务器的模板引擎。

---

## 八、init 完整流程

```
01. 读取 deploys/server.php + projects/<name>/server.php
02. 检测 Router 模式（缓存 → 远程 Docker 容器检查 → 回退自动检测）
03. SSH 连接远程服务器
04. mkdir -p <project.path>
05. git clone <repo> <path> --branch <branch>
06. git clone 子模块到 src/App/Modules/<name>
07. 渲染配置文件（本地）：
    - .env（从 template/.env + server.php env 变量）
    - docker-compose.yaml 或 docker-compose.ports.yaml（根据模式）
    - docker/nginx/sites/default.conf
    - docker/php/php.ini
    - docker/mysql/my.cnf
    - src/config/config.php
08. SCP 上传渲染后的配置文件
09. docker-compose -f <模板> up -d
10. 将域名添加到 Router（生成 nginx server block → SCP → reload）
11. 执行 afterInit 钩子（如 php artisan migration）
```

---

## 九、单元测试

部署引擎的纯逻辑部分有单元测试覆盖：

```
deploys/tests/
├── bootstrap.php                    — 加载源文件
├── helpersTest.php                  — array_get / safe_name / array_merge_deep
├── ConfigTest.php                   — 配置加载、合并、getter
├── TemplateRendererTest.php         — 单文件渲染 / renderToFile / renderDir / 边界
└── fixtures/                        — 测试用的 fixture 配置和模板
```

运行方式：`php src/vendor/bin/phpunit -c deploys/phpunit.xml`

| 组件 | 测试数 | 说明 |
|------|--------|------|
| helpers | 17 | 覆盖各类边情况 |
| Config | 12 | 加载 + 合并 + 字段提取 |
| TemplateRenderer | 16 | 渲染、目录、子目录、跳过规则 |

未测（需真实 SSH）：SSH.php、GitHelper.php、RouterManager.php 远程部分、ProjectDeployer 编排部分。

---

## 十、使用步骤

### 首次搭建

```bash
# 1. 配置服务器连接
cp deploys/server.example.php deploys/server.php
# 编辑 deploys/server.php 填入真实服务器信息

# 2. 配置项目（已有 demo/tao/yihe 示例，修改即可）
# 编辑 deploys/projects/demo/server.php

# 3. 检测服务器环境
php deploy init:router

# 4. 如果报告满意，执行安装
php deploy init:router -y

# 5. 部署项目
php deploy init demo
```

### 日常更新

```bash
php deploy upgrade demo
```

### 新增域名

```bash
# 在 server.php 的 domains 中添加域名后
php deploy nginx:add demo
```
