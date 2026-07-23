#!/bin/sh
set -e

export PORT="${PORT:-8080}"

# Fix file permissions at runtime
chown -R www-data:www-data /app
chmod -R 755 /app
chmod -R 777 /app/runtime /tmp/runtime

echo "[entrypoint] Preparing runtime directories..."
rm -rf /tmp/runtime
mkdir -p /tmp/runtime/cache /tmp/runtime/sess /tmp/runtime/log /tmp/runtime/temp
chown -R www-data:www-data /tmp/runtime /app/runtime
chmod -R 777 /tmp/runtime /app/runtime
# Nginx 需要 /run 目录
mkdir -p /run

echo "[entrypoint] Generating Nginx config for port ${PORT}..."
# 用 sed 替换端口（比 envsubst 更可靠）
sed "s/__PORT__/${PORT}/g" /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Node.js 定时器使用 localhost 回调本容器
export NODE_SCHEDULER_HOST="${NODE_SCHEDULER_HOST:-127.0.0.1}"
export NODE_SCHEDULER_PORT="${PORT}"

# 等待数据库就绪
/app/deploy/wait-for-ready.sh

echo "[entrypoint] Starting all services via supervisord on port ${PORT}..."

# supervisord 统一管理: php-fpm, nginx, nodejs-scheduler, php-cron
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
