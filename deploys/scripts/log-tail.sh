#!/bin/bash
# 日志查看工具
# 用法: log-tail.sh <类型> [行数]
#   类型: error|app|sql|nginx|php|mysql-slow|access
#   行数: 默认 50

set -e

# 获取项目根目录（脚本位于 deploys/scripts/）
PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
TYPE="${1:-error}"
LINES="${2:-50}"

case "$TYPE" in
  error|php)
    LOG="$PROJECT_DIR/docker/log/php/php_errors.log"
    ;;
  app)
    LOG=$(ls -t "$PROJECT_DIR/src/storage/logs"/app-*.log "$PROJECT_DIR/src/storage/logs"/app_*.log 2>/dev/null | head -1)
    ;;
  sql)
    LOG=$(ls -t "$PROJECT_DIR/src/storage/logs"/sql-*.log "$PROJECT_DIR/src/storage/logs"/sql_*.log 2>/dev/null | head -1)
    ;;
  nginx)
    LOG="$PROJECT_DIR/docker/log/nginx/error.log"
    ;;
  access)
    LOG="$PROJECT_DIR/docker/log/nginx/access.log"
    ;;
  mysql-slow)
    LOG="$PROJECT_DIR/docker/log/mysql/slow.log"
    ;;
  *)
    echo "用法: $0 <类型> [行数]"
    echo "类型: error|app|sql|nginx|access|mysql-slow"
    exit 1
    ;;
esac

if [ ! -f "$LOG" ]; then
  echo "日志文件不存在: $LOG"
  exit 1
fi

echo "=== $(basename "$LOG") ==="
tail -n "$LINES" "$LOG"
