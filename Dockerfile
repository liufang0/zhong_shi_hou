FROM php:7.4-fpm-bullseye

# 安装系统依赖 + Nginx + supervisord
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    nginx \
    supervisor \
    nodejs \
    npm \
    default-mysql-client \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        curl \
        mbstring \
        zip \
        bcmath \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# PHP 配置
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone=Asia/Shanghai" >> /usr/local/etc/php/conf.d/custom.ini

# PHP-FPM 配置
COPY deploy/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx 配置模板
RUN rm -f /etc/nginx/sites-enabled/default
COPY deploy/nginx.conf.template /etc/nginx/conf.d/default.conf.template

# Supervisord 配置
COPY deploy/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 应用代码
WORKDIR /app
COPY . /app

# 修复文件权限：PHP-FPM 以 www-data 运行，需要读取 /app 下文件
RUN chown -R www-data:www-data /app && chmod -R 755 /app

# 删除可能有 open_basedir 的 .user.ini
RUN rm -f /app/public/.user.ini

# 目录权限
RUN mkdir -p /app/runtime/cache /app/runtime/log /app/runtime/sess /app/runtime/temp \
    /tmp/runtime/cache /tmp/runtime/sess /tmp/runtime/log /tmp/runtime/temp \
    && chmod -R 777 /app/runtime /tmp/runtime

# Node.js 依赖
WORKDIR /app/nodejs
RUN npm install --omit=dev
WORKDIR /app

# 健康检查脚本权限
RUN chmod +x /app/deploy/healthcheck.sh /app/deploy/wait-for-ready.sh /app/deploy/entrypoint.sh

ENV PORT=8080
EXPOSE 8080

# Docker 容器级健康检查
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD ["/app/deploy/healthcheck.sh"]

ENTRYPOINT ["/app/deploy/entrypoint.sh"]
