# Migration（数据库迁移）说明

> 适用版本：自提交 `da53af377e`（2026-09-14）起。
> 代码根在 `src/`：所有命令均在 `src/` 目录下执行（`php artisan migration` / `php artisan db:seed`）。

## 1. 背景与变更概述

本次调整把迁移的 **scope 管控权下放到各模块/项目自治**，并新增了 **基础数据（seed）机制**。核心变化：

| 项 | 之前 | 现在 |
| --- | --- | --- |
| scope 定义 | 集中在 `config/migration.php` | 自动发现 `App/Modules/<名称>/config/migration.php` 与 `App/Projects/<名称>/config/migration.php` |
| 全局配置 | 大而全 | 瘦身为 `database` / `ts_based` / `order` 三项 |
| 执行顺序 | 不显式声明 | 通过 `order` 显式声明，未列出的按 scope 键排序 |
| 前缀冲突 | 无检测 | 同一 `table_prefix` 被多个 scope 管理时直接抛错 |
| 基础数据 | 无专门机制 | 新增 `php artisan db:seed`，执行幂等 SQL 并记入 `seed_history` |

对应文件改动：

- `src/config/migration.example.php` — 全局配置示例瘦身并补充说明
- `src/App/Modules/tao/Config/migration.php` / `src/App/Modules/demo/config/migration.php` — 模块迁移配置落地
- `src/tao996/Phax/Helper/MigrationHelper.php` — `loadScopes()` 支持自动发现 + order 排序 + 前缀冲突检测
- `src/tao996/Phax/Helper/SeedHelper.php` — 新增基础数据执行器
- `src/routes/cli.php` — 新增 `db:seed` 子命令
- `.gitignore` — 忽略各模块 `data/migration/.phalcon/` 内部跟踪目录

> 说明：迁移 scope 已完全下放到模块/项目自治，**不再需要**在全局配置中集中注册 scope。

---

## 2. 结构迁移（migration）

### 2.1 Scope 自动发现

`php artisan migration` 不再要求把所有 scope 写进全局配置，而是**自动扫描**：

```
App/Modules/<名称>/config/migration.php   → scope "module:<名称>"
App/Projects/<名称>/config/migration.php  → scope "project:<名称>"
```

- 文件**存在即参与迁移**；不存在则该模块/项目不参与。
- 目录名兼容大小写：`config` 与 `Config` 均可被识别。
- `directory`（迁移文件目录）按约定自动推导为
  `App/<类型>/<名称>/data/migration`，无需手动配置（也可在模块配置中覆盖）。

### 2.2 模块/项目配置写法

以 `src/App/Modules/tao/Config/migration.php` 为例：

```php
<?php
return [
    'table_prefix' => 'tao_',   // 该模块管理的表前缀（generate 时自动匹配）
    // 以下表的初始数据随迁移一起导出和还原
    'export' => [
        'tao_cms_page'      => 'always',
        'tao_open_config'   => 'always',
        'tao_system_config' => 'always',
        'tao_system_menu'   => 'always',
        'tao_system_node'   => 'always',
        'tao_system_user'   => 'always',
    ],
];
```

`demo` 模块最小配置（仅声明前缀）：

```php
<?php
return [
    'table_prefix' => 'demo_',
];
```

支持的键：

- `table_prefix`（可选）：生成的 `tables` 默认等于该前缀 + `*`。
- `export`（可选）：随迁移导出/还原数据的表，值为 `'always'` 表示始终导出。
- `directory`（可选）：覆盖约定目录。

### 2.3 全局配置（config/migration.php）

由 `src/config/migration.example.php` 拷贝而来，仅保留全局项：

```php
return [
    'database' => [ /* 目标库连接，可选；不填则用应用默认连接 */ ],
    'ts_based' => true,
    'order' => [
        'module:tao',   // 基础模块务必排最前
    ],
];
```

| 键 | 说明 |
| --- | --- |
| `database` | 目标数据库连接（适配 `phalcon-migrations`）。不填则回退到应用 `database.stores.<default>`。 |
| `ts_based` | 是否使用时间戳版本号（`true/false`）。注意 `run/list` 内部强制为 `false`，以匹配 `generate` 始终使用递增版本号（如 `1.0.0`）的行为。 |
| `order` | 执行顺序声明，值为 scope 键或模块名（如 `'tao'` 会被补全为 `'module:tao'`）。`order` 中的项排最前，其余按 scope 键排序追加。 |

### 2.4 执行顺序与冲突检测

- **排序规则**：先按 `order` 列表（基础模块如 `tao` 务必排前，因为其表常被其他模块/seed 依赖），其余 scope 按 key 升序。`db:seed` 同样复用该 `order`。
- **前缀冲突检测**：若多个 scope 声明了**相同的 `table_prefix`**，加载时直接抛错，提示修正模块配置（例如 `table_prefix 冲突：'tao_' 同时被 ... 与 ... 管理`）。

### 2.5 命令

```bash
cd src
php artisan migration                 # 显示帮助
php artisan migration g               # 生成所有 scope 的迁移文件
php artisan migration g --scope=module:demo   # 仅生成 demo
php artisan migration g --m=demo      # 等价简写
php artisan migration r               # 运行所有 scope 迁移
php artisan migration r --scope=module:tao    # 仅运行 tao
php artisan migration l               # 列出所有 scope 迁移
```

`--scope` 也可简写为 `--m=`（模块）/ `--p=`（项目）。

其它可传参数（`g` / `r` / `l` 内部透传到 `Phax\Helper\MigrationHelper`）：

- `tables` / `datas` / `data_method` / `version` / `force`
- `no_auto_increment` / `skip_ref_schema` / `descr` / `dry` / `config` / `skip`
- `log_in_db` / `verbose` / `skip_foreign_checks`

---

## 3. 基础数据（seed）

结构迁移只管表结构；跨项目共享的**初始/基础数据**由新增的 `db:seed` 机制负责。

### 3.1 设计要点

- **文件位置**：默认扫描 `App/Modules/<名称>/data/seed/*.sql` 与 `App/Projects/<名称>/data/seed/*.sql`，文件随模块/项目仓库走。
- **幂等要求**：seed 文件**必须幂等**，使用 `REPLACE INTO` / `ON DUPLICATE KEY UPDATE`，可重复执行。
- **记账机制**：`seed_history` 表按 `scope + 文件 + 内容 hash` 记账（首次自动建表）。只执行**新增或内容变化**的文件；已执行且 hash 不变的自动跳过；hash 变化（内容修改）会重新执行。
- **顺序**：同目录内文件名建议带序号（如 `001-xxx.sql`），按文件名升序执行；scope 间顺序复用迁移的 `order`。
- **外部目录**：`--dir=` 可追加外部目录（如 deploy 上传的项目差异数据），scope 记为 `external`。

### 3.2 命令

```bash
cd src
php artisan db:seed                 # 执行模块/项目下 data/seed/*.sql
php artisan db:seed --dir=xxx       # 追加外部目录（相对 PATH_ROOT 或绝对路径）
```

执行输出示例：

```
执行: module:tao/001-system_menu.sql
seed 完成: 执行 1 个，跳过 5 个
  + module:tao/001-system_menu.sql
  - module:tao/002-system_node.sql
  ...
```

### 3.3 记账表结构（seed_history）

```sql
CREATE TABLE IF NOT EXISTS seed_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope VARCHAR(64) NOT NULL DEFAULT '',
  file VARCHAR(255) NOT NULL DEFAULT '',
  hash CHAR(32) NOT NULL DEFAULT '',
  executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_scope_file (scope, file)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.4 注意事项

- MySQL 的 **DDL 会隐式提交事务**。若 seed 文件包含 DDL（建表/改表）且中途失败，无法回滚；需人工修正后重跑——因 ledger 未记账，该文件会整体重新执行。
- 数据库连接优先使用 `config/migration.php` 的 `database.dbname`（与结构迁移同一目标库），缺省回退应用默认连接。

---

## 4. 典型工作流

1. **新增模块**：在 `App/Modules/<名称>/Config/migration.php` 声明 `table_prefix`、可选的 `export`、`directory`。
2. **生成结构迁移**：`php artisan migration g --scope=module:<名称>`。
3. **导出基础数据**：把初始数据写成幂等 SQL 放到 `App/Modules/<名称>/data/seed/*.sql`。
4. **一键部署**：
   ```bash
   php artisan migration r          # 结构迁移
   php artisan db:seed              # 基础数据
   ```
5. **项目差异数据**：通过 `php artisan db:seed --dir=deploy/xxx` 注入不在仓库内的数据。

---

## 5. 其它说明

- **`.gitignore`**：已新增忽略 `src/App/Modules/*/data/migration/.phalcon/`，无需手动处理 phalcon-migrations 内部跟踪目录。
- **运行模式差异**：CLI 下通过 `src/artisan` → `src/bootstrap/app.php` 加载，迁移/seed 均在 `IS_TASK` 模式下执行。
