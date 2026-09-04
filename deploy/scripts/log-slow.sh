#!/bin/bash
# 慢查询分析
# 用法: log-slow.sh [阈值秒] [行数]
#   阈值: 默认 1 秒
#   行数: 默认 20

PROJECT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
THRESHOLD="${1:-1}"
LINES="${2:-20}"
SLOW_LOG="$PROJECT_DIR/docker/log/mysql/slow.log"

if [ ! -f "$SLOW_LOG" ]; then
  echo "慢查询日志不存在: $SLOW_LOG"
  exit 1
fi

echo "=== 慢查询统计（阈值 > ${THRESHOLD}s）==="

# 提取 Query_time 和 SQL
awk -v t="$THRESHOLD" '
  /^# Query_time:/ {
    qtime = $3
    if (qtime + 0 > t + 0) {
      print "---"
      print $0
    }
  }
  /^# User@Host:/ { print }
  /^\t/ && qtime != "" {
    qsec = qtime + 0
    if (qsec > t + 0) print $0
  }
' "$SLOW_LOG" | tail -n "$LINES"

echo ""
echo "=== 最慢 10 条 ==="
grep 'Query_time:' "$SLOW_LOG" | \
  sed 's/.*Query_time: //;s/  Lock_time:.*//' | \
  sort -rn | head -10 | \
  while read -r t; do
    echo "  ${t}s"
  done
