# Railway 部署指南 — 石油项目

## 一、项目架构

```
Railway Project
├── MySQL 插件         ← 数据库
├── PHP 后端服务       ← hou/ 目录（含 Nginx + PHP-FPM + Node.js 定时器 + PHP Cron）
└── Web 前端           ← web/ 目录（可选：单独部署或集成到后端）
```

## 二、部署步骤

### 第 1 步：准备 Git 仓库

将 `hou/` 目录推送到一个 Git 仓库（GitHub / GitLab）：

```bash
cd hou/
git init
git add .
git commit -m "initial commit"
git remote add origin https://github.com/你的用户名/你的仓库.git
git push -u origin main
```

> **注意**：`web/` 目录是独立的前端 SPA，需单独部署或合并到 `hou/` 仓库。

### 第 2 步：Railway 创建项目

1. 登录 https://railway.com
2. 点击 **New Project**
3. 选择 **Deploy from GitHub repo** → 选择你的仓库
4. Railway 会自动检测到 `Dockerfile` 并开始构建

### 第 3 步：添加 MySQL 插件

1. 在项目面板中点击 **+ New** → **Database** → **Add MySQL**
2. Railway 会自动创建 MySQL 实例
3. 数据库连接变量（`MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`）会自动注入到你的服务中

### 第 4 步：导入数据库

方法一：通过 Railway CLI：
```bash
# 安装 Railway CLI
npm install -g @railway/cli

# 登录
railway login

# 连接到项目
railway link

# 导入 SQL
railway connect mysql
# 进入 mysql shell 后：
source /path/to/liu.sql
```

方法二：通过 MySQL 客户端连接（Railway 面板 → MySQL → Connect → 复制连接信息）：
```bash
mysql -h <MYSQL_HOST> -P <MYSQL_PORT> -u <MYSQL_USER> -p<MYSQL_PASSWORD> <MYSQL_DATABASE> < liu.sql
```

### 第 5 步：配置环境变量

在 Railway 项目面板 → 你的服务 → **Variables** 中添加：

| 变量名 | 值 | 说明 |
|--------|-----|------|
| `MYSQL_HOST` | *(自动注入)* | MySQL 插件会自动设置 |
| `MYSQL_PORT` | *(自动注入)* | MySQL 插件会自动设置 |
| `MYSQL_USER` | *(自动注入)* | MySQL 插件会自动设置 |
| `MYSQL_PASSWORD` | *(自动注入)* | MySQL 插件会自动设置 |
| `MYSQL_DATABASE` | *(自动注入)* | MySQL 插件会自动设置 |
| `NODE_SCHEDULER_HOST` | `127.0.0.1` | Node.js 定时器访问本地 Nginx |
| `NODE_SCHEDULER_PORT` | `8080` | 内部 Nginx 端口 |
| `PORT` | `8080` | Railway 默认端口 |

> Railway 的 MySQL 插件会自动注入 `MYSQL_*` 和 `MYSQLDATABASE_*` 前缀的变量。
> 如果变量名不完全匹配，可在 Variables 中手动添加引用，例如：
> `MYSQL_HOST = ${{MySQL.MYSQLHOST}}`

### 第 6 步：配置域名

1. Railway 面板 → 你的服务 → **Settings** → **Networking**
2. 点击 **Generate Domain** 获取 `*.up.railway.app` 域名
3. 或者绑定自定义域名（需要在 DNS 添加 CNAME 记录）

### 第 7 步：部署前端 Web（方案选择）

#### 方案 A：集成到后端服务（推荐）

将 `web/` 目录的内容复制到 `hou/` 仓库中：
```bash
mkdir -p hou/web
cp -r web/* hou/web/
```

Nginx 已配置了 `/web/` 路径来提供前端静态文件。访问 `https://你的域名/web/` 即可。

#### 方案 B：单独部署

在 Railway 项目中再添加一个服务，用于 `web/` 目录：
1. 创建一个新仓库放 `web/` 内容
2. 添加一个简单的 `Dockerfile`：
```dockerfile
FROM nginx:alpine
COPY . /usr/share/nginx/html
EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]
```
3. 修改前端中 API 请求地址指向后端服务域名

---

## 三、已修改的文件

| 文件 | 改动 |
|------|------|
| `config/database.php` | 数据库连接改为读取环境变量，本地开发 fallback 到原值 |
| `nodejs/data.js` | Host/Port 改为读取环境变量，内部走 HTTP |
| `Dockerfile` | **新建** - PHP 7.4 + Nginx + Supervisor 多进程容器 |
| `deploy/nginx.conf` | **新建** - Nginx 配置（URL 重写、静态文件、PHP-FPM） |
| `deploy/supervisord.conf` | **新建** - Supervisor 管理 4 个进程 |
| `deploy/php-fpm.conf` | **新建** - PHP-FPM 池配置 |
| `cron.sh` | **新建** - 适配容器路径的定时脚本 |
| `railway.toml` | **新建** - Railway 构建配置 |
| `.dockerignore` | **新建** - Docker 构建排除 |
| `.gitignore` | **新建** - 排除 runtime/cache、runtime/log、runtime/sess、runtime/temp 等运行期产物 |

---

## 四、注意事项

### 1. 文件上传持久化
Railway 的文件系统是**临时的**（每次部署会重置）。`public/upload/` 中的用户上传文件会丢失。

**解决方案**：
- 使用对象存储（阿里云 OSS、七牛云等）替代本地上传 — 项目已集成了 `qiniu` 和 `aliyuncs` SDK
- 或使用 Railway Volume（持久化存储）

### 2. Session 存储
当前 Session 存储在文件系统 `runtime/sess/`，重新部署会丢失。生产环境建议改为 Redis 或 数据库 Session。

### 3. 运行期目录（不应提交到 Git）
以下目录为运行期自动生成的产物，已通过 `.gitignore` 排除，**不应提交到版本控制**：

| 目录 | 用途 |
|------|------|
| `runtime/cache/` | ThinkPHP 缓存文件 |
| `runtime/log/` | 应用运行日志 |
| `runtime/sess/` | Session 会话文件 |
| `runtime/temp/` | 临时文件 |

这些目录在应用启动时自动创建，部署时无需手动处理。

### 4. 安全注意
- `config/wechat.php` 中包含敏感密钥，建议迁移到环境变量
- `config/database.php` 已改为环境变量读取
- 确保 Railway 服务的环境变量中不暴露敏感信息

### 5. PHP 版本
ThinkPHP 5 要求 PHP >= 5.6，Dockerfile 使用 PHP 7.4（平衡兼容性和性能）。

### 6. 费用
Railway 免费套餐：每月 $5 额度 + 500 小时执行时间。MySQL 插件会消耗额外资源。超出后需升级至 Hobby 计划（$5/月）或 Pro 计划。

---

## 五、本地测试 Docker 构建

```bash
cd hou/
docker build -t shiyou-app .
docker run -p 8080:8080 \
  -e MYSQL_HOST=host.docker.internal \
  -e MYSQL_PORT=3306 \
  -e MYSQL_DATABASE=liu \
  -e MYSQL_USER=liu \
  -e MYSQL_PASSWORD=008851 \
  -e NODE_SCHEDULER_HOST=127.0.0.1 \
  -e NODE_SCHEDULER_PORT=8080 \
  shiyou-app
```

访问 http://localhost:8080 测试。
