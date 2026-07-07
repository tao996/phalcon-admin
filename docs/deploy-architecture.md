# 部署架构设计 — ReverseProxy + DockerNetwork 模式

> 设计日期：2025-07-07
> 状态：待实现

---

## 一、核心拓扑

```
┌─────────────────────────────────────────────────────────┐
│                     宿主服务器                            │
│                                                         │
│  host:80/443                                            │
│     ↓                                                   │
│  ┌──────────────┐    共享网络: phalanx-shared            │
│  │   router     │  ────┬────┬────┬────                  │
│  │   nginx      │      │    │    │                      │
│  └──────────────┘      │    │    │                      │
│         │              │    │    │                      │
│         │ proxy_pass   │    │    │                      │
│         ├─────  demo-nginx:80    │                      │
│         ├─────────  tao-nginx:80 │                      │
│         └───────────── yihe-nginx:80                    │
│                                                         │
│  每个项目有自己的 default 网络（nginx+php+mysql+redis）    │
│  仅 nginx 同时接入「项目内网」+「phalanx-shared」          │
└─────────────────────────────────────────────────────────┘
```

### 关键规则

- **只有 router 映射 host 端口**（80/443）
- **每个业务项目的容器不暴露任何 host 端口**
- 每个项目的 nginx 同时连接两个网络：
  - `default`（项目内网，与 php-fpm 通信）
  - `phalanx-shared`（共享网络，被 router 访问）
- 项目间的 mysql/redis 默认隔离；需要共享的服务（如共用 redis）可选择性加入共享网络

### 流量路径

```
用户访问 demo.example.com
  → Host:80 → Router Nginx
  → 根据 server_name 匹配 → proxy_pass http://demo-nginx:80
  → demo-nginx 处理静态文件 / 代理到 demo-php:9000
```

---

## 二、目录结构

```
项目根目录/
├── deploy                     — PHP CLI 入口
├── deploys/
│   ├── shared-network.php     — 共享网络定义（名称: phalanx-shared）
│   ├── router/                — Router 项目
│   │   ├── server.php         — Router 自身的部署配置
│   │   ├── nginx/
│   │   │   ├── router.conf.example   — 主体 nginx 配置
│   │   │   └── upstream/             — 每个项目的 server block 片段
│   │   │       └── example.conf      — 示例片段
│   │   └── docker-compose.yaml       — Router compose（仅 nginx 服务）
│   ├── projects/              — 各业务项目
│   │   └── <project-name>/
│   │       ├── server.php     — 部署配置（服务器、仓库、域名、变量等）
│   │       └── commands/
│   │           └── after-up.sh       — docker-compose up 后的自定义钩子
│   ├── template/              — 通用模板（可选 fallback）
│   └── lib/                   — 部署引擎核心
│       ├── SSH.php            — SSH/SFTP 远程执行封装
│       ├── Router.php         — Router 配置管理（增删 upstream、reload）
│       ├── Project.php        — 项目生命周期
│       ├── ConfigRenderer.php — .example → 正式配置渲染
│       └── DeployCommand.php  — CLI 命令路由
```

---

## 三、Router 设计

Router 本身也是一个 Docker 容器，是整个集群的流量入口。

### docker-compose

```yaml
services:
  router:
    image: nginx:stable-alpine
    container_name: phalanx-router
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/router.conf:/etc/nginx/conf.d/default.conf:ro
      - ./nginx/upstream/:/etc/nginx/upstream/:ro
      - /etc/letsencrypt:/etc/letsencrypt:ro   # SSL 证书
    networks:
      - phalanx-shared
    restart: always

networks:
  phalanx-shared:
    external: true
```

### nginx 配置策略

```
/etc/nginx/conf.d/default.conf  → http 块基础配置
/etc/nginx/upstream/*.conf      → 每个项目一个独立 server block
```

每个项目一个 conf 片段（由 `deploy init` 自动生成）：

```nginx
# /etc/nginx/upstream/demo.conf
server {
    listen 80;
    server_name demo.example.com;

    location / {
        proxy_pass http://demo-nginx:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SSL 版本
    # listen 443 ssl;
    # ssl_certificate /etc/letsencrypt/live/demo.example.com/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/demo.example.com/privkey.pem;
}
```

---

## 四、项目配置格式（server.php）

不再需要端口管理，配置大大简化：

```php
<?php
return [
    'ssh' => [
        'ip'       => '1.2.3.4',
        'port'     => 22,
        'username' => 'root',
        'keyFile'  => '~/.ssh/id_rsa',
        // 或 'password' => 'xxx'
    ],
    'project' => [
        'name'       => 'demo',               // 项目名（也是容器名前缀）
        'repo'       => 'git@github.com:user/phalcon-admin.git',
        'branch'     => 'master',
        'path'       => '/root/projects/demo',
        'modules'    => [
            'demo' => '',                     // 空=主仓库已有，非空=单独 git 地址
        ],
        'domain'     => 'demo.example.com',
        'ssl'        => false,
    ],
    'config' => [
        // .example 模板渲染时的变量替换（{{VAR}} 占位符）
        'APP_TITLE'  => 'Demo Admin',
        'DB_NAME'    => 'phalcon_demo',
        'REDIS_DB'   => '1',
        'JWT_SECRET' => 'xxx',
    ],
];
```

### 与之前版本的区别

| 旧字段 | 新字段 | 说明 |
|--------|--------|------|
| `ports` | 无 | 整个架构消除了端口映射 |
| nginx 主机配置 | `domain` | 只需域名，nginx 配置由工具生成 |
| 大量 example 变量的手动填写 | `config` | 统一在 server.php 中声明 |

---

## 五、CLI 命令集

### Router 管理

| 命令 | 功能 |
|------|------|
| `php deploy router:init` | 首次：安装 Docker → 创建 phalanx-shared 网络 → 启动 router |
| `php deploy router:reload` | 重载 nginx 配置 |
| `php deploy router:ssl <domain>` | 为域名配置 Let's Encrypt 证书 |

### 项目管理

| 命令 | 功能 |
|------|------|
| `php deploy init <project>` | 首次部署 |
| `php deploy upgrade <project>` | 更新代码和配置 |
| `php deploy status <project>` | 查看容器状态 |
| `php deploy logs <project> [service]` | 实时日志 |

---

## 六、init 完整流程

```
01. 验证 server.php 配置合法
02. SSH 连接远程服务器
03. mkdir -p <project.path>
04. git clone <repo> <path> [--branch <branch>]
05. git clone 子模块到 src/App/Modules/<name>
06. 渲染配置文件（本地生成）：
    - .env（从 .env.example + server.php config 变量）
    - docker-compose.yaml（加入 phalanx-shared 外部网络，仅 nginx 加入）
    - docker/php/php.ini（从 php.prod.ini 或 php.example.ini）
    - docker/nginx/sites/*.conf
07. SCP 上传渲染后的配置文件到服务器
08. 远程执行：cd <path> && docker-compose up -d
09. 远程执行：容器就绪后执行数据库迁移等初始化
10. 生成 nginx upstream 片段（域名 → 服务名映射）
11. SCP 上传 upstream 片段到 router 的 nginx/upstream/ 目录
12. SSH 执行 router nginx -s reload
13. 输出部署摘要（域名、访问地址、状态）
```

如 router 与项目在同一台服务器（最常见），步骤 11-12 在同一 SSH 会话完成；
如 router 在独立服务器，则需在步骤 10-12 切换 SSH 连接。

---

## 七、项目 docker-compose.yaml 调整

### 改动点

1. **去掉所有 services 的 `ports:`** — 不暴露宿主机端口
2. **nginx 加一个外网** — 加入 `phalanx-shared` 供 router 访问

### 模板结构

```yaml
services:
  nginx:
    image: nginx:stable-alpine
    # ports:            ← 去掉，不需要暴露
    #   - "8071:80"
    volumes:
      - ./docker/nginx/sites:/etc/nginx/conf.d:ro
      - ./src:/var/www:ro
    networks:
      - default
      - phalanx-shared    ← 加入共享网络

  php:
    build: ./docker/images
    # ports:            ← 去掉（php-fpm 不走端口）
    volumes:
      - ./src:/var/www
    networks:
      - default            ← 只在项目内网
    environment:
      - IS_PHP_FPM=1

  mysql:
    image: mysql:8.1.0
    # ports:            ← 去掉
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - default
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}

  redis:
    image: redis:7.2-alpine
    # ports:            ← 去掉
    networks:
      - default

networks:
  default:
    driver: bridge
  phalanx-shared:
    external: true
    name: phalanx-shared

volumes:
  mysql-data:
```

---

## 八、实现路线（三个阶段）

### Phase 1：基础框架

目标：一条命令完成首次部署，router 配置手动补充

- [ ] 创建 `deploys/lib/` 核心类（SSH、ConfigRenderer、Project）
- [ ] 实现 `php deploy init` 流程步骤 1-9
- [ ] 实现 `php deploy upgrade` 流程
- [ ] 创建第一个项目 `deploys/projects/<name>/server.php`

### Phase 2：Router 自动化

目标：init 自动完成 router upstream 注册

- [ ] 创建 `deploys/router/` 完整配置
- [ ] 实现 `php deploy router:init`
- [ ] 实现 `php deploy router:reload`
- [ ] `deploy init` 集成步骤 10-13

### Phase 3：运维能力

- [ ] `php deploy status`
- [ ] `php deploy logs`
- [ ] SSL 自动配置（certbot 集成）
- [ ] 数据库备份/同步

---

## 九、注意与备忘

1. **容器命名**：router 固定为 `phalanx-router`；项目 nginx 命名为 `<project>-nginx`（如 `demo-nginx`），以便 router 的 proxy_pass 能通过 Docker DNS 解析
2. **首次部署**：必须先初始化 router，再部署业务项目
3. **SSH 认证**：使用密钥对优先，`server.php` 中配置 `keyFile`；密码也可用但不推荐
4. **.env 变量**：`config.php` 中声明的内容会自动渲染到 `.env.example` → `.env`；不随 git 提交的敏感值（JWT secret、数据库密码）应在 `server.php` 中定义
5. **phalanx-shared 网络**：名称固定，所有项目和 router 使用同一个
