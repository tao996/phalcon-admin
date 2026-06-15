.PHONY: docs xdebug-web-on xdebug-web-off xdebug-cli php

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
