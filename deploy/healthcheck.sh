#!/bin/sh
# healthcheck.sh — Docker HEALTHCHECK 探针脚本
# 通过 /health.php 端点检查 Apache + PHP + 数据库是否全部就绪
set -e

PORT="${PORT:-8080}"
URL="http://localhost:${PORT}/health.php"

# 使用 curl 检查健康端点，-f 表示 HTTP 4xx/5xx 时返回非零退出码
if curl -sf --max-time 5 "$URL" > /dev/null 2>&1; then
    exit 0
else
    exit 1
fi
