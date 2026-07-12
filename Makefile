.PHONY: docs xdebug-web-on xdebug-web-off xdebug-cli php ui-install ui-run ui-pack

UI_VENV = deploysUI/.venv
UI_MAIN = deploysUI/main.py

# ─── Docs ───────────────────────────────────────────────
docs:
	mkdocs serve --livereload -a 127.0.0.1:8001

# ─── Xdebug ─────────────────────────────────────────────

PHP_INI = docker/php/php.example.ini
DC_PHP  = docker-compose exec php

# 开启 Web 请求的 Xdebug（所有请求自动触发，无需浏览器 cookie）
xdebug-web-on:
	@sed -i '' 's/start_with_request=trigger/start_with_request=yes/' $(PHP_INI)
	@docker-compose restart php
	@echo "✓ Xdebug: 每次 Web 请求自动触发 (client_port=19003, idekey=docker)"

# 恢复为 trigger 模式（需要 XDEBUG_SESSION cookie/header 才触发）
xdebug-web-off:
	@sed -i '' 's/start_with_request=yes/start_with_request=trigger/' $(PHP_INI)
	@docker-compose restart php
	@echo "✓ Xdebug: 恢复 trigger 模式（需浏览器扩展发送 XDEBUG_SESSION）"

# CLI 调试：对一条命令开启 Xdebug
# 用法: make xdebug-cli CMD="php artisan test"
xdebug-cli:
	@$(DC_PHP) -e XDEBUG_MODE=debug -e XDEBUG_SESSION=1 $(CMD)

# ─── PHP 快捷入口 ───────────────────────────────────────
# 用法: make php CMD="artisan test"
php:
	@$(DC_PHP) php $(CMD)

# 导出配置为 JSON
ui-export:
	@php deploy config:export

# ─── Deploy UI ──────────────────────────────────────
# 安装依赖
ui-install:
	@test -d $(UI_VENV) || python3 -m venv $(UI_VENV)
	@. $(UI_VENV)/bin/activate && pip install -r deploysUI/requirements.txt -q
	@echo "✓ 依赖已安装"

# 启动 UI
ui-run: ui-install
	@. $(UI_VENV)/bin/activate && python $(UI_MAIN)

# 一键打包为独立可执行文件
ui-pack: ui-install
	@. $(UI_VENV)/bin/activate && pip install pyinstaller -q
	@. $(UI_VENV)/bin/activate && PYINSTALLER_CONFIG_DIR=/tmp/pyinstaller pyinstaller --onefile --windowed $(UI_MAIN) --name deployUI --distpath dist
	@echo "✓ 打包完成: dist/deployUI"
