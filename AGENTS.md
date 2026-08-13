# AGENTS.md — Phalcon Admin

基于 **Phalcon 5.13（C 扩展）** 的多模块后台管理系统（demo / tao / yihe 三个业务模块 + 自定义框架层）。

## Project

- **真实代码根在 `src/`**：`composer.json`、`vendor/`、`phpunit.xml`、`public/index.php`、`artisan` 全部在 `src/` 下；仓库根目录没有 composer 文件。
- 入口：Web = `src/public/index.php`，CLI = `src/artisan` → `src/bootstrap/app.php`，子命令注册在 `src/routes/cli.php`。
- 栈：PHP 8.3+（ext-curl/redis/pdo/gd/intl/bcmath/xlswriter，见 `src/composer.json`）、Phalcon 5.13（docker 镜像 `authus/phalcon:5.13.0`）、PHPUnit 11.2 + Mockery。
- 金额计算统一走 `Phax\Utils\MyBc`（`bcadd` 返回 string，注意转 `(float)`）。

## Commands（在 `src/` 目录下执行）

- `php artisan` — CLI 入口；子命令：`test`、`metadata`、`migration`、`cc`（codeception 快捷）、`minify`。
- `php artisan test` — 跑 PHPUnit（实际执行 `vendor/bin/phpunit -c phpunit.xml`，即 `src/phpunit.xml`）。
- `php artisan migration` — 执行 phalcon-migrations（`src/tao996/phar/phalcon-migrations.phar`）。
- `docker-compose up -d` — 启动 nginx（宿主机 `8071`）+ php-fpm；**mysql/redis 服务已在 docker-compose.yaml 中注释**。
- 测试需要 MySQL 测试库 `phalcon-admin-test`；本机无此库时 PHPUnit 报 `SQLSTATE[HY000] [1049] Unknown database`，属环境问题、与代码改动无关。

## Architecture

- `src/App/`：应用代码 — `Http/`（Controllers、views）、`Modules/`、`Console/`（MainTask.php）、`Projects/`（demo）。
- `src/App/Modules/{demo,tao,yihe}/`：模块继承 `\Phax\Mvc\Module`。`tao` 提供基类（`BaseController`、`BaseAuthController`、`BaseRbacController`、`BaseTaoModel`）与 layui 前端封装；`yihe` 是业务模块（Customer / Trip / Payment / CustomerBalanceHistory 等模型 + Services + Helpers）。
- `src/tao996/Phax/`：自定义框架层（Bridge、Db、Mvc、Support、Helper、Events）；`src/tao996/phar/` 存放 phar 类库（minify、phpseclib、phalcon-migrations）。
- `src/config/`：`config.php` 为配置入口，include `services.docker.example.php` / `services.local.example.php` 等；dev 覆盖直接写在 `config.php`。
- 请求流：`public/index.php` → `bootstrap/app.php` → Router（`src/routes/web.php`）→ Module → `{name}Action()` 返回数组自动渲染视图。

## Conventions

- 控制器动作命名 `{name}Action()`，返回数组供自动视图渲染；layui 页面常用 `form.val('form-search')` 取查询条件、`admin.util.layOn` 绑定按钮事件、`window.location.href` 携带查询参数跳转。
- RBAC 用 **PHP 8 Attribute** `#[RBAC(title: '...', close: 1)]`（类定义在 `src/App/Modules/tao/Helper/Libs/RBAC.php`），作用于类与方法。
- 模型查询用 QueryBuilder 链式方法（`->int()` / `->between()` / `->findModels()` 等）。
- PSR-4：`src/composer.json` 映射 `App\` → `app/`（**小写**），但实际目录是 `App/`（大写）——运行时靠 `src/tao996/index.php` 中的 `PATH_APP` 常量，**不要改目录名**。

## Watch out for

- **代码根在 `src/`**：vendor、composer.json、phpunit.xml 都不在仓库根目录；`php artisan` 需在 `src/` 下执行。
- `src/App/Modules/*` 被 .gitignore 忽略，改动不会出现在 `git diff` / `git status` 中。
- 运行时模式：`IS_PHP_FPM` / `IS_TASK` 常量（定义于 `src/public/index.php` / `src/bootstrap/app.php`），不同模式下逻辑差异大。
- 双 PHPUnit 配置：`src/phpunit.xml`（phax 测试）+ `src/phpunit.example.xml`（示例）；`php artisan test` 使用前者。
- Docker first：宿主机配置在 `.env`（`OPEN_PORT=8071`），容器挂载 `./src:/var/www`。

## Notes

- 如果没有检测到 php 运行程序，先向客户确认当前是否安装了 php 或者项目运行在 docker 之中