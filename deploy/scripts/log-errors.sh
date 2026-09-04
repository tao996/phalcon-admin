#!/bin/bash
# 最近错误汇总
# 用法: log-errors.sh [时间范围]
#   时间范围: 字符串传递给 grep，如 "2025-07-08"（默认今天）

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
SINCE="${1:-$(date +%F)}"

echo "━━━━━ 错误汇总: $SINCE ━━━━━"

echo ""
echo "--- PHP 错误 ---"
PHP_LOG="$PROJECT_DIR/docker/log/php/php_errors.log"
if [ -f "$PHP_LOG" ]; then
  grep "$SINCE" "$PHP_LOG" | grep -i 'error\|Warning\|Fatal' | tail -20
else
  echo "(无)"
fi

echo ""
echo "--- Nginx 错误 ---"
NGX_LOG="$PROJECT_DIR/docker/log/nginx/error.log"
if [ -f "$NGX_LOG" ]; then
  grep "$SINCE" "$NGX_LOG" | grep -i 'error\|warn' | tail -20
else
  echo "(无)"
fi

echo ""
echo "--- 慢查询 ---"
SLOW_LOG="$PROJECT_DIR/docker/log/mysql/slow.log"
if [ -f "$SLOW_LOG" ]; then
  grep "Query_time" "$SLOW_LOG" | tail -10
else
  echo "(无)"
fi

echo ""
echo "--- 应用日志最近错误 ---"
APP_LOG=$(ls -t "$PROJECT_DIR/src/storage/logs"/app-*.log 2>/dev/null | head -1)
if [ -n "$APP_LOG" ]; then
  grep -i 'error\|exception\|致命' "$APP_LOG" | tail -10
else
  echo "(无)"
fi
