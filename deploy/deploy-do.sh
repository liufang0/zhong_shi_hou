#!/bin/bash
# ============================================
# DigitalOcean Droplet 一键部署脚本
# 在服务器上执行: bash deploy.sh
# ============================================
set -e

echo "===== 1. 系统更新 + 安装 Docker ====="
apt-get update && apt-get upgrade -y
apt-get install -y ca-certificates curl gnupg lsb-release

# 安装 Docker
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" > /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 启动 Docker
systemctl enable docker
systemctl start docker

echo "===== 2. 添加 Swap (1GB) ====="
fallocate -l 1G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

echo "===== 3. 创建项目目录 ====="
mkdir -p /app
cd /app

echo "===== 4. 创建 .env 文件 ====="
if [ ! -f .env ]; then
    cat > .env << 'EOF'
# 数据库配置 - 请修改为实际值
MYSQL_HOST=db
MYSQL_PORT=3306
MYSQL_DATABASE=liu
MYSQL_USER=liu
MYSQL_PASSWORD=CHANGE_ME_STRONG_PASSWORD
MYSQL_ROOT_PASSWORD=CHANGE_ME_STRONG_ROOT_PASSWORD
PORT=8080

# 微信支付配置 - 请修改为实际值
WECHAT_APPID=CHANGE_ME
WECHAT_APPSECRET=CHANGE_ME
WECHAT_MCH_ID=CHANGE_ME
WECHAT_MCH_KEY=CHANGE_ME
EOF
    echo "已创建 .env 模板，请修改其中的密码和凭据。"
else
    echo ".env 已存在，跳过创建。"
fi

echo "===== 5. 创建 docker-compose.yml ====="
cat > docker-compose.yml << 'EOF'
services:
  app:
    image: your-app-image
    ports:
      - "8080:8080"
    env_file: .env
    depends_on:
      db:
        condition: service_healthy
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=${MYSQL_DATABASE}
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 5s
      retries: 10
    restart: unless-stopped

volumes:
  db_data:
EOF

echo "===== 6. 安装 Nginx 反向代理 + HTTPS ====="
apt-get install -y nginx certbot python3-certbot-nginx

# Nginx 反向代理配置
cat > /etc/nginx/sites-available/app << 'EOF'
server {
    listen 80;
    server_name _;  # 改成你的域名，如 api.example.com

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

ln -sf /etc/nginx/sites-available/app /etc/nginx/sites-enabled/app
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo ""
echo "============================================"
echo "✅ 基础环境部署完成！"
echo ""
echo "接下来你需要做的："
echo "  1. 把代码传到 /app 目录"
echo "  2. 构建 Docker 镜像"
echo "  3. 修改 .env 中的 MYSQL_ROOT_PASSWORD"
echo "  4. 配置域名和 HTTPS"
echo ""
echo "详细步骤见下一条消息"
echo "============================================"
