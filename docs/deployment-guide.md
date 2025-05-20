# 部署指南

## 概述

本文档详细描述了企业微信综合管理系统的部署流程，包括系统要求、安装步骤、配置说明、安全设置等内容。请按照本指南进行系统部署，以确保系统正常运行。

## 系统要求

### 硬件要求

- CPU: 4核及以上
- 内存: 8GB及以上
- 硬盘: 100GB及以上（SSD推荐）
- 带宽: 10Mbps及以上

### 软件要求

- 操作系统: Ubuntu 20.04 LTS / CentOS 8+
- Web服务器: Nginx 1.18+
- PHP: 8.0+
- MySQL: 8.0+
- Redis: 6.0+
- Node.js: 16.0+
- Git: 2.0+

## 安装步骤

### 1. 系统准备

```bash
# 更新系统
sudo apt update
sudo apt upgrade -y

# 安装基础软件包
sudo apt install -y curl wget git unzip supervisor
```

### 2. 安装PHP及扩展

```bash
# 添加PHP仓库
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# 安装PHP及常用扩展
sudo apt install -y php8.0-fpm php8.0-cli php8.0-mysql php8.0-curl \
    php8.0-gd php8.0-mbstring php8.0-xml php8.0-zip php8.0-bcmath \
    php8.0-intl php8.0-redis php8.0-opcache

# 配置PHP
sudo vim /etc/php/8.0/fpm/php.ini

# 修改以下配置
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 60
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60

# 重启PHP-FPM
sudo systemctl restart php8.0-fpm
```

### 3. 安装MySQL

```bash
# 安装MySQL
sudo apt install -y mysql-server

# 配置MySQL
sudo mysql_secure_installation

# 创建数据库和用户
mysql -u root -p
```

```sql
CREATE DATABASE wework_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wework_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON wework_admin.* TO 'wework_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. 安装Redis

```bash
# 安装Redis
sudo apt install -y redis-server

# 配置Redis
sudo vim /etc/redis/redis.conf

# 修改以下配置
maxmemory 256mb
maxmemory-policy allkeys-lru

# 重启Redis
sudo systemctl restart redis
```

### 5. 安装Node.js

```bash
# 使用NVM安装Node.js
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install --lts
```

### 6. 安装Nginx

```bash
# 安装Nginx
sudo apt install -y nginx

# 配置Nginx
sudo vim /etc/nginx/sites-available/wework-admin

# 添加以下配置
server {
    listen 80;
    server_name example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name example.com;

    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    root /var/www/wework-admin/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
    }
    
    location = /robots.txt { 
        access_log off; 
        log_not_found off; 
    }

    error_log /var/log/nginx/wework_error.log;
    access_log /var/log/nginx/wework_access.log;
}

# 创建符号链接并测试配置
sudo ln -s /etc/nginx/sites-available/wework-admin /etc/nginx/sites-enabled/
sudo nginx -t

# 重启Nginx
sudo systemctl restart nginx
```

### 7. 安装SSL证书

```bash
# 安装Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取SSL证书
sudo certbot --nginx -d example.com
```

### 8. 部署应用

```bash
# 创建部署目录
sudo mkdir -p /var/www/wework-admin
sudo chown -R $USER:$USER /var/www/wework-admin

# 克隆项目
git clone [项目仓库地址] /var/www/wework-admin

# 安装Composer依赖
cd /var/www/wework-admin
composer install --no-dev --optimize-autoloader

# 安装前端依赖并构建
cd frontend
npm install
npm run build

# 配置环境变量
cd ..
cp .env.example .env
php artisan key:generate

# 编辑环境变量
vim .env

# 运行数据库迁移
php artisan migrate --force

# 创建存储目录链接
php artisan storage:link

# 设置目录权限
sudo chown -R www-data:www-data /var/www/wework-admin
sudo find /var/www/wework-admin -type f -exec chmod 644 {} \;
sudo find /var/www/wework-admin -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache
```

### 9. 配置队列处理

```bash
# 创建Supervisor配置
sudo vim /etc/supervisor/conf.d/wework-worker.conf

# 添加以下配置
[program:wework-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wework-admin/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/wework-admin/storage/logs/worker.log
stopwaitsecs=3600

# 更新Supervisor配置
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

### 10. 配置定时任务

```bash
# 编辑Crontab
crontab -e

# 添加Laravel调度器
* * * * * cd /var/www/wework-admin && php artisan schedule:run >> /dev/null 2>&1
```

## 配置说明

### 1. 环境变量配置

```env
APP_NAME=企业微信综合管理系统
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wework_admin
DB_USERNAME=wework_user
DB_PASSWORD=your_strong_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

WEWORK_CORPID=your_corpid
WEWORK_SECRET=your_secret
WEWORK_AGENT_ID=your_agent_id
WEWORK_TOKEN=your_token
WEWORK_AES_KEY=your_aes_key
```

### 2. 文件权限配置

```bash
# 设置存储目录权限
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache

# 设置上传目录权限
sudo chmod -R 775 storage/app/public
```

### 3. 缓存配置

```bash
# 优化配置加载
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 如果需要清除缓存
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 安全设置

### 1. 防火墙配置

```bash
# 安装UFW
sudo apt install -y ufw

# 配置防火墙规则
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 2. 系统安全加固

```bash
# 禁用密码登录，使用SSH密钥
sudo vim /etc/ssh/sshd_config

# 修改以下配置
PasswordAuthentication no
PermitRootLogin no

# 重启SSH服务
sudo systemctl restart sshd

# 定期更新系统
sudo apt update
sudo apt upgrade -y
```

### 3. 数据库安全

```bash
# MySQL安全配置
sudo mysql_secure_installation

# 定期备份数据库
# 创建备份脚本
vim /usr/local/bin/backup-database.sh

#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/mysql"
MYSQL_USER="backup_user"
MYSQL_PASSWORD="your_backup_password"
DATABASE="wework_admin"

# 创建备份目录
mkdir -p "$BACKUP_DIR"

# 备份数据库
mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$DATABASE" | gzip > "$BACKUP_DIR/$DATABASE-$TIMESTAMP.sql.gz"

# 删除30天前的备份
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete

# 设置执行权限
chmod +x /usr/local/bin/backup-database.sh

# 添加到定时任务
crontab -e
0 2 * * * /usr/local/bin/backup-database.sh
```

## 性能优化

### 1. PHP优化

```ini
; php.ini 优化配置
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1

realpath_cache_size=4096K
realpath_cache_ttl=600
```

### 2. MySQL优化

```ini
# my.cnf 优化配置
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
```

### 3. Nginx优化

```nginx
# nginx.conf 优化配置
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 65535;
    multi_accept on;
    use epoll;
}

http {
    # 开启gzip压缩
    gzip on;
    gzip_comp_level 5;
    gzip_min_length 256;
    gzip_proxied any;
    gzip_types
        application/javascript
        application/json
        application/xml
        text/css
        text/plain
        text/xml;

    # 缓存配置
    open_file_cache max=1000 inactive=20s;
    open_file_cache_valid 30s;
    open_file_cache_min_uses 2;
    open_file_cache_errors on;

    # 客户端缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

## 维护指南

### 1. 日常维护任务

```bash
# 检查日志文件
sudo tail -f /var/log/nginx/wework_error.log
sudo tail -f /var/www/wework-admin/storage/logs/laravel.log

# 检查队列状态
sudo supervisorctl status

# 检查系统资源使用情况
htop
df -h
free -m

# 检查服务状态
sudo systemctl status nginx
sudo systemctl status php8.0-fpm
sudo systemctl status redis
sudo systemctl status mysql
```

### 2. 更新流程

```bash
# 进入项目目录
cd /var/www/wework-admin

# 启用维护模式
php artisan down

# 备份数据库
/usr/local/bin/backup-database.sh

# 拉取最新代码
git pull origin main

# 安装依赖
composer install --no-dev --optimize-autoloader

# 更新前端资源
cd frontend
npm install
npm run build
cd ..

# 运行数据库迁移
php artisan migrate --force

# 清除缓存
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 重新生成缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 重启队列处理器
sudo supervisorctl restart all

# 设置权限
sudo chown -R www-data:www-data .
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod -R 775 storage bootstrap/cache

# 关闭维护模式
php artisan up
```

### 3. 故障处理

#### 网站无法访问

1. 检查服务状态
```bash
sudo systemctl status nginx
sudo systemctl status php8.0-fpm
```

2. 检查错误日志
```bash
sudo tail -f /var/log/nginx/wework_error.log
sudo tail -f /var/www/wework-admin/storage/logs/laravel.log
```

3. 检查权限设置
```bash
sudo chown -R www-data:www-data /var/www/wework-admin
sudo chmod -R 755 /var/www/wework-admin
sudo chmod -R 775 /var/www/wework-admin/storage
```

#### 队列处理异常

1. 检查队列状态
```bash
sudo supervisorctl status

# 如果需要重启队列
sudo supervisorctl restart all
```

2. 检查队列日志
```bash
tail -f /var/www/wework-admin/storage/logs/worker.log
```

#### 数据库连接失败

1. 检查MySQL服务状态
```bash
sudo systemctl status mysql
```

2. 检查连接参数
```bash
mysql -u wework_user -p
```

3. 检查错误日志
```bash
sudo tail -f /var/log/mysql/error.log
```

## 监控方案

### 1. 系统监控

推荐使用以下工具之一：
- Prometheus + Grafana
- New Relic
- Datadog

### 2. 应用监控

```bash
# 安装Laravel Telescope（仅在需要时启用）
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### 3. 日志监控

推荐使用ELK Stack：
- Elasticsearch
- Logstash
- Kibana

## 备份策略

### 1. 数据库备份

- 每日全量备份
- 实时binlog备份
- 定期测试恢复流程

### 2. 文件备份

- 定期备份上传文件
- 备份配置文件
- 异地备份重要数据

## 扩展建议

### 1. 负载均衡

当需要扩展系统时，可以考虑：
- 使用Nginx负载均衡
- 使用云服务商的负载均衡服务
- 实现会话共享

### 2. 缓存优化

- 使用Redis集群
- 实现多级缓存
- 合理设置缓存策略

### 3. 数据库优化

- 主从复制
- 读写分离
- 分表分库

## 参考资源

1. [Laravel部署最佳实践](https://laravel.com/docs/8.x/deployment)
2. [Nginx官方文档](https://nginx.org/en/docs/)
3. [MySQL优化指南](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
4. [PHP性能优化](https://www.php.net/manual/en/book.opcache.php)
5. [企业微信开发文档](https://work.weixin.qq.com/api/doc)

## 更新记录

### [1.0.0] - 2024-01-17
- 初始版本
- 基础部署流程
- 配置说明
- 维护指南