#!/bin/sh
# wait-for-ready.sh — 启动等待脚本
# 在 Apache 启动前等待 MySQL 数据库就绪，避免服务未就绪就接收流量
set -e

DB_HOST="${MYSQL_HOST:-127.0.0.1}"
DB_PORT="${MYSQL_PORT:-3306}"
MAX_RETRIES=30
INTERVAL=2

echo "[wait-for-ready] Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."

retries=0
while [ $retries -lt $MAX_RETRIES ]; do
    # 使用 mysqladmin ping（如可用）或 TCP 探测
    if command -v mysqladmin >/dev/null 2>&1; then
        if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" \
             -u"${MYSQL_USER:-root}" -p"${MYSQL_PASSWORD:-}" \
             --silent 2>/dev/null; then
            echo "[wait-for-ready] MySQL is ready!"
            exit 0
        fi
    else
        # TCP 探测回退：用 /dev/tcp 或 nc
        if command -v nc >/dev/null 2>&1; then
            if nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; then
                echo "[wait-for-ready] MySQL port is open!"
                exit 0
            fi
        else
            # 最后的回退：尝试建立 TCP 连接
            if (echo > /dev/tcp/"$DB_HOST"/"$DB_PORT") 2>/dev/null; then
                echo "[wait-for-ready] MySQL port is open!"
                exit 0
            fi
        fi
    fi

    retries=$((retries + 1))
    echo "[wait-for-ready] Attempt ${retries}/${MAX_RETRIES} - MySQL not ready, retrying in ${INTERVAL}s..."
    sleep "$INTERVAL"
done

echo "[wait-for-ready] WARNING: MySQL not reachable after $((MAX_RETRIES * INTERVAL))s, starting anyway..."
exit 0
