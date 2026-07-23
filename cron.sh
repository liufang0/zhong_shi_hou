#!/bin/sh
# 替代原 start.sh，路径适配 Railway 容器
while true
do
  cd /app/public
  php index.php /index/api.index/order_crontab
  sleep 2
  php index.php /index/api.index/ajaxdata
  sleep 2
done
