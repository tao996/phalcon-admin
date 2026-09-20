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
- `version`（可选）：本模块迁移的版本号，如 `'1.0.0'`。**默认即 `1.0.0`，各模块无需（也不应）手动错开版本号**——每个模块在文件模式下使用各自独立的追踪文件（`data/migration/.phalcon/migration-version`），互不干扰（详见第 8.2 节）。
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
2. **生成结构迁移**：`php artisan migration g --m=<名称>`。
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

---

## 6. 实践演示：迁移到新数据库 `phalcon-migration-test`

场景：已新建空数据库 `phalcon-migration-test`，并在 `src/config/migration.php` 中把 `database.dbname` 指向它。下面把现有模块的表结构与基础数据迁移过去。

### 6.1 前置条件（当前已满足）

- 目标库 `phalcon-migration-test` 已创建。
- `src/config/migration.php` 的 `database` 已指向该库（`dbname => 'phalcon-migration-test'`）。
- 迁移文件已存在（无需重新生成）：
  - `tao`：`App/Modules/tao/data/migration/1.0.0/` 下 **27 个 `.php`**（tao\_* 表结构）+ **6 个 `.dat`**（基础数据，对应 `export` 约定的 `tao_cms_page` / `tao_open_config` / `tao_system_config` / `tao_system_menu` / `tao_system_node` / `tao_system_user`）。
  - `demo`：`App/Modules/demo/data/migration/1.0.0/` 下 **6 个 `.php`**（demo\_* 表结构，无 `.dat`）。
- 全局 `order = ['module:tao']`，故执行顺序为 tao 先于 demo。

### 6.2 步骤一：预览将执行哪些迁移

```bash
cd src
php artisan migration l
```

预期：依次列出 `module:tao`、`module:demo` 两个 scope，以及各自 `1.0.0` 版本的迁移文件。因目标库为空，这些迁移均处于「待执行」状态。

### 6.3 步骤二：执行结构迁移并还原基础数据

```bash
php artisan migration r
```

预期执行结果：

1. **`module:tao` 先执行**：创建 27 张 `tao_*` 表；并还原 `export` 约定中 6 张表的基础数据（读取对应的 `.dat` 文件）。
2. **`module:demo` 后执行**：创建 6 张 `demo_*` 表（仅结构，无 `.dat`）。
3. phalcon-migrations 在目标库自动建立迁移记录表（默认 `phalcon_migrations`），登记已应用的版本 `1.0.0`。

> 若遇到外键顺序导致的报错，可加 `--skip_foreign_checks` 跳过外键检查：
> ```bash
> php artisan migration r --skip_foreign_checks
> ```

### 6.4 步骤三：校验结果

```bash
php artisan migration l
```

预期：刚才的迁移显示为「已应用」，列表不再出现待执行项。

也可直接连库核对：

```sql
USE phalcon-migration-test;
SHOW TABLES;                       -- 应包含 27 张 tao_* 与 6 张 demo_*
SELECT COUNT(*) FROM tao_system_menu;   -- 应 > 0（基础数据已随 .dat 还原）
```

### 6.5 步骤四：补充基础数据（`db:seed`，按需）

```bash
php artisan db:seed
```

预期（当前）：各模块 `data/seed/` 下暂无 `*.sql` 文件，输出：

```
seed 完成: 执行 0 个，跳过 0 个
```

如需注入跨项目共享的基础数据，按第 3 节规则在模块 `data/seed/` 下放置幂等 SQL（如 `001-xxx.sql`），再执行一次即可；已执行且内容未变的文件会被 `seed_history` 跳过，只有新增/变化的文件会重跑。

### 6.6 完整命令清单

```bash
cd src
php artisan migration l            # 1. 预览
php artisan migration r            # 2. 结构迁移 + 还原 .dat 基础数据
php artisan migration l            # 3. 校验
php artisan db:seed                # 4. 补充基础数据（按需）
```

### 6.7 备注：若某模块还没有迁移文件

本项目 `tao` / `demo` 已生成迁移文件；若是全新模块，需先从「含表结构的源库」生成迁移文件，再 `run` 到目标库：

1. 新建一个指向**源库**（已建好表）的配置文件，例如 `src/config/source.php`，**不要改动**正式的 `migration.php`：

   ```php
   <?php
   return ['database' => [ /* 源库连接，含已建好的表 */ ]];
   ```

2. 从源库生成迁移文件（写入该模块的 `data/migration/`）：

   ```bash
   php artisan migration g --m=tao --config=config/source.php
   ```

3. 再执行 `php artisan migration r`，即会把结构（及 `export` 表的 `.dat` 数据）应用到目标库 `phalcon-migration-test`。

---

## 7. 实践演示：从开发库 `phalcon-admin` 生成迁移供其它服务器使用

场景：你在本机用 `src/config/config.php` 连接开发库 `phalcon-admin`（`'dbname' => 'phalcon-admin'`）开发了一段时间，表结构和基础数据都已就绪。现在要把这些变更**导出为迁移文件**提交到仓库，以便其它服务器（或测试/生产环境）用 `migration r` 一键重建同样的库。

### 7.1 核心原理（generate 与 run 的数据库是分开的）

- **`generate`（生成）**：从「源库」读取表结构与（export 声明的）数据，写出迁移文件。源库由 `--config=<文件>` 指定，或回退到 `migration.php` 的 `database`。
- **`run`（执行）**：把迁移文件应用到「目标库」。目标库由 `migration.php` 的 `database` 决定。

因此推荐做法：**用 `--config=` 指向开发库 `phalcon-admin` 来生成**，而**不要改动 `migration.php` 里部署用的目标库配置**，这样生成动作与部署目标互不干扰。注意 `config.php`（`dbname => 'phalcon-admin'`）是应用运行时连接，迁移工具并不读它，无需修改。

### 7.2 步骤一：准备指向开发库的源配置

新建 `src/config/migration.source.php`（仅本地用，可按需加入 `.gitignore`）：

```php
<?php
// 指向本机开发库 phalcon-admin
return ['database' => [
    'adapter'  => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'phalcon-admin',   // 开发库
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'   => '',
    'options'  => [
        \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ],
]];
```

> 若 `migration.php` 里 `database` 当前指向的就是本机 `phalcon-admin`，也可省略此文件，直接 `g`（会回退读取 `migration.php` 的 `database`）。但用独立 `--config` 更清晰、也更安全。

### 7.3 步骤二：为每个模块/项目生成迁移

```bash
cd src
php artisan migration g --m=tao  --config=config/migration.source.php
php artisan migration g --m=demo --config=config/migration.source.php
# 若有更多模块/项目，依次补上：--m=yihe / --p=xxx
```

预期执行结果：

- 从 `phalcon-admin` 读取 `tao_*` / `demo_*` 表结构，写入：
  - `App/Modules/tao/data/migration/1.0.0/*.php`（每张表一个迁移类）
  - `App/Modules/tao/data/migration/1.0.0/*.dat`（仅 `export` 配置声明的表，如 `tao_system_menu`、`tao_system_user` 等基础数据）
  - `App/Modules/demo/data/migration/1.0.0/*.php`
- 版本号为递增式 `1.0.0`（与 `r`/`l` 一致）；再次生成会更新同一版本目录。

> 哪些表的数据会随迁移一起走，由模块 `config/migration.php` 的 `export` 决定（见第 2.2 节）。只想迁结构、不迁数据，就不要在 `export` 里列该表。

### 7.4 步骤三：提交迁移文件

把生成的 `App/Modules/*/data/migration/` 目录提交到仓库（注意 `.gitignore` 已忽略其中的 `.phalcon/` 内部跟踪目录，无需处理）。其它服务器拉取代码后即拥有完整迁移。

### 7.5 步骤四：在其它服务器上重建库

在目标服务器上，把 `src/config/migration.php` 的 `database` 指向该服务器自己的数据库（如 `phalcon-admin-prod`），然后：

```bash
cd src
php artisan migration r     # 按 order 应用所有 scope 的迁移，建表并还原 .dat 基础数据
php artisan db:seed         # 若该服务器还需补充 data/seed/*.sql 基础数据
```

目标库会从空库变成与开发库 `phalcon-admin` 一致的表结构与基础数据。

### 7.6 完整流程一览

```
开发机（源库 phalcon-admin）                 其它服务器（目标库）
─────────────────────────                   ─────────────────────
migration.source.php ← 指向 phalcon-admin
migration g --m=tao  --config=...  ──┐
migration g --m=demo --config=...    │  提交 data/migration/*
                                     │
                                     └──►  拉取代码
                                          migration.php → 指向本机库
                                          migration r
                                          db:seed（按需）
```

> 备选方案：若不想新建 `migration.source.php`，可临时把 `migration.php` 的 `database.dbname` 改为 `phalcon-admin`，执行 `migration g`，再改回目标库。但容易误提交，**更推荐用 `--config=`**。

---

## 8. 故障排查

### 8.1 `Info: No one table is created. You should create tables first.`

执行 `migration g --m=demo --config=config/migration.source.php` 报此错，通常有两种原因：

1. **`--config=` 未被生效（旧版本 Bug）**：早期 `MigrationHelper::parser()` 只把 `--scope/--m/--p` 透传给 `generate()`，会**静默丢弃 `--config=`**，导致 generate 回退到 `migration.php` 的 `database`（本例为空的 `phalcon-migration-test`），从而读不到任何 `demo_*` 表。该问题已在代码层修复（`parser()` 现在通过 `collectOptions()` 透传 `--config` 等全部选项）。请先 `git pull`/应用修复后再试。
2. **源库里确实没有对应前缀的表**：即便 `--config` 生效，若 `phalcon-admin` 中并不存在 `demo_*`（或对应 `table_prefix`）的表，同样会报此错。请先确认源库已建好这些表（例如用 `SHOW TABLES LIKE 'demo_%';` 核对）。

修复 / 核对后重新执行即可：

```bash
php artisan migration g --m=demo --config=config/migration.source.php
```

预期会看到 `App/Modules/demo/data/migration/1.0.0/` 下生成 `demo_*.php`（以及 `export` 表的 `.dat`）。

### 8.2 某模块显示 `Info: Everything is up to date` 但库里没有它的表

现象：`migration r` 先成功迁移了 `module:tao`，随后 `module:demo` 直接报 `Everything is up to date`，目标库里却没有 `demo_*` 表。

**真实根因（不是版本号问题）**：`MigrationHelper::parser()` 在一个进程内用 `foreach` 依次对每个 scope 调用 `run()`/`generate()`。而 phalcon-migrations 的 `Migrations` 内部用 **静态属性 `self::$storage`** 缓存「迁移追踪位置」。其 `connectionSetup()` 开头有：

```php
if (self::$storage) {
    return;   // 已初始化则直接返回，复用上一次的追踪位置
}
```

因此：第一个 scope（`tao`）执行时初始化了 `self::$storage`（默认文件模式 → `tao/data/migration/.phalcon/migration-version`）；第二个 scope（`demo`）进 `connectionSetup` 时 `self::$storage` 仍为真，**直接 return**，于是 demo 复用了 tao 的追踪文件，读到其中已记录的 `1.0.0`，误判自己「已执行」→ 整模块被跳过。

**修复**（在 `MigrationHelper` 的 `generate/run/list` 调用前调用一次重置）：

```php
Migrations::resetStorage();   // 清空静态 storage，让下一个 scope 重新初始化自己的追踪位置
ob_start();
Migrations::run([ ... ]);
```

加上这行后，每个 scope 都会重新建立属于自己的 `.phalcon/migration-version`（文件模式默认即「每模块独立目录、独立追踪文件」），多模块之间**天然隔离**，无需手动错开 `version`。

> 注意：若你曾按旧方案为每个模块手工设置不同 `version`（如 demo=`2.0.0`）来绕过此问题，**现在可以撤销**——把各模块 `config/migration.php` 里的 `version` 键删掉即可，统一用默认 `1.0.0`。若磁盘上残留了旧的 `demo/data/migration/2.0.0` 目录，删掉即可（`rmdir /s /q App\Modules\demo\data\migration\2.0.0`）。

**仍建议排查的小点**：确保每个模块的 `directory` 真实独立（`App/Modules/<名称>/data/migration`），自动发现已保证这一点；只要追踪文件各自独立，多个 `1.0.0` 不会互相干扰。
