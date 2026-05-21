# REASONIX.md — Phalcon Admin

## Stack

- **PHP** 8.3+ (ext-curl, ext-redis, ext-pdo, ext-gd, ext-intl, ext-ssh2 required)
- **Phalcon** — C-extension PHP framework; runs as php-fpm or Workerman
- **Workerman** — PHP socket framework for HTTP + WebSocket servers
- **PHPUnit** 11.2 + **Mockery** 1.6 — test framework
- **Docker** — dev environment via `docker-compose.yaml` (nginx + php-fpm + mysql + redis)

## Layout

- `src/` — main application root (entry: `public/index.php` for web, `artisan` for CLI)
- `src/App/` — application code (Controllers, Modules, Console, Projects, Workerman)
- `src/App/Modules/` — multi-module structure (demo, tao, yihe modules)
- `src/tao996/Phax/` — custom framework layer (Bridge, Db, Mvc, Support, Helper, Events)
- `src/config/` — app config (`config.php`, `services.example.php`, `migration.example.php`)
- `src/tests/` — phax-framework unit tests
- `docker/` — Docker config (nginx sites, php ini, supervisor, mysql init SQL)
- `toolkit/` — standalone helper scripts (sync, deploy, SSH, Docker management)
- `toolkit/tests/` — toolkit unit tests
- `mkdocs/` — documentation source

## Commands

- `php artisan` — CLI entry point (see `src/routes/cli.php` for registered sub-commands)
- `php artisan test` — run PHPUnit tests (uses `phpunit.xml` at project root for toolkit tests)
- `php artisan migration` — run phalcon-migrations
- `php artisan cc` — shortcut for Codeception (`vendor/bin/codecept`)
- `docker-compose up -d` — start dev environment (nginx:8071, Workerman HTTP:8072, WS:8073, MySQL:13306, Redis:16379)

## Conventions

- **Controller actions** use `{name}Action()` naming (Phalcon convention), return arrays for auto-view rendering
- **Modules** extend `\Phax\Mvc\Module` and live under `src/App/Modules/{name}/`
- **RBAC** via `@rbac ({title:'...', close:1})` annotations on controllers and modules
- **PSR-4 autoload**: `App\` → `App/` (note: composer.json maps `app/` lowercase, actual dir is `App/` — works inside Docker via runtime constant `PATH_APP`)
- **Config** loads from `config.php` which includes `services.example.php` — dev overrides are placed directly in `config.php`
- **Two separate PHPUnit configs**: `phpunit.xml` (root, for toolkit tests) and `src/phpunit.example.xml` (for phax tests)
- **Environment** via `.env` (Docker Compose variables) + Symfony Dotenv phar for app-level env

## Watch out for

- **composer.json autoload mismatch**: maps `App\\` to `app/` (lowercase), but the actual directory is `App/` (uppercase). The project works via `PATH_APP` constant set in `tao996/index.php` — do not rename the directory to lowercase.
- **Dual PHPUnit configs**: `phpunit.xml` at root runs toolkit tests; `src/phpunit.example.xml` runs phax tests. Running `php artisan test` runs the root config.
- **phalcon extension toggle**: `php artisan phalcon` disables/re-enables the C-extension — needed when running the pure-PHP phar fallback (`tao996/phar-src/`).
- **Runtime mode detection**: code branches on `IS_PHP_FPM`, `IS_WORKER_WEB`, `IS_TASK` constants — logic paths differ significantly between modes.
- **Docker first**: the project is designed to run inside Docker — host config in `.env`, services in `docker-compose.yaml`.
