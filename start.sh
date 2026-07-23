while true
do
  cd /www/wwwroot/hou/public
  php index.php /index/api.index/order_crontab
  sleep 2
  php index.php /index/api.index/ajaxdata
  sleep 2
done