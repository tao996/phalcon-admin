#!/bin/bash
# 一键健康检查
# 用法: doctor.sh

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"

echo "━━━━━ 健康检查 ━━━━━"
echo "项目: $(basename "$PROJECT_DIR")"
echo "时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

echo "--- 磁盘使用 ---"
df -h "$PROJECT_DIR" 2>/dev/null | tail -1 | awk '{print "  已用 "$3" / 总计 "$2" ("$5")"}'

echo ""
echo "--- Docker 容器状态 ---"
cd "$PROJECT_DIR"
COMPOSE_FILE="docker-compose.ports.yaml"
if [ ! -f "$COMPOSE_FILE" ]; then
  COMPOSE_FILE="docker-compose.yaml"
fi
if [ -f "$COMPOSE_FILE" ]; then
  docker compose -f "$COMPOSE_FILE" ps --format "table {{.Name}}\t{{.Status}}" 2>/dev/null || echo "  Docker 未运行"
else
  echo "  未找到 docker-compose 文件"
fi

echo ""
echo "--- 进程状态 ---"
echo -n "  Nginx: "
if pgrep -x nginx >/dev/null 2>&1; then echo "运行中"; else echo "未运行"; fi
echo -n "  PHP-FPM: "
if pgrep -x php-fpm >/dev/null 2>&1; then echo "运行中"; else echo "未运行"; fi
echo -n "  MySQL: "
if pgrep -x mysqld >/dev/null 2>&1; then echo "运行中"; else echo "未运行"; fi
echo -n "  Redis: "
if pgrep -x redis-server >/dev/null 2>&1; then echo "运行中"; else echo "未运行"; fi

echo ""
echo "--- 最近错误 ---"
bash "$(dirname "$0")/log-errors.sh" "$(date +%F)" 2>/dev/null | head -30
