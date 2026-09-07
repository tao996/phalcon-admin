.PHONY: docs dc-xdebug-web-on dc-xdebug-web-off dc-xdebug-cli dc-php serve xdebug-serve xdebug-cli xdebug-info

# 本地内置服务器端口
ServerPort ?= 9002

# 本地 Xdebug 客户端端口（IDE 监听端口，与本机 php.ini 保持一致）
XdebugPort ?= 51712

# ─── Docs ───────────────────────────────────────────────
docs:
	@. .venv/bin/activate && mkdocs serve --livereload -a 127.0.0.1:8001

# ─── Xdebug Docker ─────────────────────────────────────────────
# docker 下配置
PHP_INI = docker/php/php.example.ini
DC_PHP  = docker-compose exec php

# 开启 Web 请求的 Xdebug（所有请求自动触发，无需浏览器 cookie）
dc-xdebug-web-on:
	@sed -i '' 's/start_with_request=trigger/start_with_request=yes/' $(PHP_INI)
	@docker-compose restart php
	@echo "✓ Xdebug: 每次 Web 请求自动触发 (client_port=19003, idekey=docker)"

# Docker: 恢复为 trigger 模式（需要 XDEBUG_SESSION cookie/header 才触发）
dc-xdebug-web-off:
	@sed -i '' 's/start_with_request=yes/start_with_request=trigger/' $(PHP_INI)
	@docker-compose restart php
	@echo "✓ Xdebug: 恢复 trigger 模式（需浏览器扩展发送 XDEBUG_SESSION）"

# CLI 调试：对一条命令开启 Xdebug
# 用法: make xdebug-cli CMD="php artisan test"
dc-xdebug-cli:
	@$(DC_PHP) -e XDEBUG_MODE=debug -e XDEBUG_SESSION=1 $(CMD)

# ─── PHP 快捷入口 ───────────────────────────────────────
# 用法: make php CMD="artisan test"
dc-php:
	@$(DC_PHP) php $(CMD)

# 用 PHP 内置服务器启动本地站点
# 用法: make serve [ServerPort=9002]
serve:
	@php -S localhost:$(ServerPort) -t ./src/public ./src/public/index.php

# ─── Xdebug 本地（非 docker） ───────────────────────────────────────
# 仅通过 php -d 参数覆盖，不修改全局 php.ini

# 本地: 启动带 Xdebug 的内置服务器（每次 Web 请求自动触发调试）
# 用法: make xdebug-serve [ServerPort=9002] [XdebugPort=51712]
xdebug-serve:
	@echo "✓ 本地 Xdebug: 每次 Web 请求自动触发 (client_port=$(XdebugPort))"
	@php -S localhost:$(ServerPort) -t ./src/public ./src/public/index.php \
	  -d xdebug.mode=debug \
	  -d xdebug.start_with_request=yes \
	  -d xdebug.client_port=$(XdebugPort)

# 本地 CLI 调试：对一条命令开启 Xdebug
# 用法: make xdebug-cli CMD="php unit_test.php"
xdebug-cli:
	@php -d xdebug.mode=debug -d xdebug.start_with_request=yes $(CMD)

# 查看当前本地 Xdebug 配置
xdebug-info:
	@php -i | grep -E "xdebug\.(mode|client_host|client_port|start_with_request|idekey)"