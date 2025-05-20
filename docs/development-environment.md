# 开发环境配置指南

## 概述

本文档详细描述了企业微信综合管理系统的开发环境配置过程，包括系统要求、软件安装、环境配置、项目初始化等内容。开发人员可以按照本指南快速搭建开发环境。

## 系统要求

### 硬件要求

- CPU: 双核及以上
- 内存: 8GB及以上
- 硬盘: 20GB及以上可用空间

### 操作系统

- Windows 10/11
- macOS 10.15+
- Ubuntu 20.04+/CentOS 8+

## 软件安装

### 1. PHP环境

#### Windows

1. 下载并安装 [XAMPP](https://www.apachefriends.org/index.html) (包含PHP 8.0+, MySQL, Apache)
   ```
   安装步骤:
   1. 运行安装程序
   2. 选择组件: Apache, MySQL, PHP, phpMyAdmin
   3. 选择安装目录
   4. 完成安装
   ```

2. 配置PHP
   ```
   1. 打开 C:\xampp\php\php.ini
   2. 启用以下扩展:
      - extension=curl
      - extension=fileinfo
      - extension=gd
      - extension=mbstring
      - extension=mysqli
      - extension=openssl
      - extension=pdo_mysql
      - extension=redis
   3. 设置内存限制: memory_limit = 256M
   4. 设置上传文件大小: upload_max_filesize = 20M
   5. 设置POST数据大小: post_max_size = 20M
   ```

#### macOS

1. 使用Homebrew安装PHP
   ```bash
   # 安装Homebrew
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   
   # 安装PHP
   brew install php@8.0
   
   # 安装MySQL
   brew install mysql
   
   # 启动MySQL
   brew services start mysql
   ```

2. 配置PHP
   ```bash
   # 编辑PHP配置文件
   sudo vim /usr/local/etc/php/8.0/php.ini
   
   # 启用必要的扩展并设置参数
   # 与Windows相同
   ```

#### Linux (Ubuntu)

1. 安装PHP和相关扩展
   ```bash
   # 更新软件包列表
   sudo apt update
   
   # 安装PHP和常用扩展
   sudo apt install php8.0 php8.0-cli php8.0-common php8.0-curl php8.0-gd php8.0-mbstring php8.0-mysql php8.0-xml php8.0-zip php8.0-redis
   
   # 安装MySQL
   sudo apt install mysql-server
   
   # 启动MySQL
   sudo systemctl start mysql
   ```

2. 配置PHP
   ```bash
   # 编辑PHP配置文件
   sudo vim /etc/php/8.0/cli/php.ini
   
   # 设置参数
   # 与Windows相同
   ```

### 2. Composer

Composer是PHP的依赖管理工具，用于安装和管理项目依赖。

#### Windows

1. 下载并安装 [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe)
2. 按照安装向导完成安装

#### macOS/Linux

```bash
# 下载安装脚本
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# 验证安装脚本
php -r "if (hash_file('sha384', 'composer-setup.php') === '55ce33d7678c5a611085589f1f3ddf8b3c52d662cd01d4ba75c0ee0459970c2200a51f492d557530c71c15d8dba01eae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# 安装Composer
php composer-setup.php

# 移动到全局目录
sudo mv composer.phar /usr/local/bin/composer

# 删除安装脚本
php -r "unlink('composer-setup.php');"
```

### 3. Node.js和npm

前端开发需要Node.js环境。

#### Windows

1. 下载并安装 [Node.js](https://nodejs.org/)
2. 选择LTS版本
3. 按照安装向导完成安装

#### macOS

```bash
# 使用Homebrew安装
brew install node
```

#### Linux

```bash
# 使用NVM安装Node.js
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install --lts
```

### 4. Git

版本控制工具。

#### Windows

1. 下载并安装 [Git for Windows](https://git-scm.com/download/win)
2. 按照安装向导完成安装

#### macOS

```bash
# 使用Homebrew安装
brew install git
```

#### Linux

```bash
# Ubuntu/Debian
sudo apt install git

# CentOS
sudo yum install git
```

### 5. Redis

缓存服务器。

#### Windows

1. 下载 [Redis for Windows](https://github.com/microsoftarchive/redis/releases)
2. 解压到指定目录
3. 运行 redis-server.exe

#### macOS

```bash
# 使用Homebrew安装
brew install redis

# 启动Redis
brew services start redis
```

#### Linux

```bash
# Ubuntu/Debian
sudo apt install redis-server

# 启动Redis
sudo systemctl start redis
```

## 环境配置

### 1. 配置虚拟主机

#### Apache (XAMPP)

1. 编辑httpd-vhosts.conf
   ```
   # Windows: C:\xampp\apache\conf\extra\httpd-vhosts.conf
   # macOS/Linux: /etc/apache2/sites-available/000-default.conf
   
   <VirtualHost *:80>
       DocumentRoot "/path/to/project/public"
       ServerName wework-admin.local
       <Directory "/path/to/project/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. 编辑hosts文件
   ```
   # Windows: C:\Windows\System32\drivers\etc\hosts
   # macOS/Linux: /etc/hosts
   
   127.0.0.1 wework-admin.local
   ```

3. 重启Apache
   ```
   # Windows: XAMPP控制面板
   # macOS/Linux
   sudo systemctl restart apache2
   ```

### 2. 配置MySQL

1. 创建数据库
   ```sql
   CREATE DATABASE wework_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. 创建用户并授权
   ```sql
   CREATE USER 'wework_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON wework_admin.* TO 'wework_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

### 3. 配置Redis

默认配置通常足够开发使用，无需特别配置。

## 项目初始化

### 1. 克隆项目

```bash
# 克隆项目仓库
git clone [项目仓库地址] wework-admin
cd wework-admin
```

### 2. 安装依赖

```bash
# 安装PHP依赖
composer install

# 安装前端依赖
cd frontend
npm install
cd ..
```

### 3. 配置环境变量

```bash
# 复制环境变量示例文件
cp .env.example .env

# 编辑.env文件
# 设置数据库连接信息
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wework_admin
DB_USERNAME=wework_user
DB_PASSWORD=your_password

# 设置Redis连接信息
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 设置企业微信API信息
WEWORK_CORPID=your_corpid
WEWORK_SECRET=your_secret
WEWORK_AGENT_ID=your_agent_id
WEWORK_TOKEN=your_token
WEWORK_AES_KEY=your_aes_key
```

### 4. 初始化数据库

```bash
# 运行数据库迁移
php artisan migrate

# 填充初始数据
php artisan db:seed
```

### 5. 生成应用密钥

```bash
php artisan key:generate
```

### 6. 启动开发服务器

```bash
# 启动后端服务
php artisan serve

# 启动前端开发服务器
cd frontend
npm run dev
```

## 开发工具推荐

### IDE/编辑器

1. **Visual Studio Code**
   - 推荐扩展:
     - PHP Intelephense
     - PHP Debug
     - Laravel Blade Snippets
     - Vue Language Features
     - ESLint
     - Prettier
     - GitLens

2. **PhpStorm**
   - 专业PHP开发IDE
   - 内置Laravel支持
   - 强大的代码补全和导航

### 调试工具

1. **Xdebug**
   - 安装:
     ```bash
     # Windows (XAMPP)
     # 下载对应版本的Xdebug DLL并放入PHP扩展目录
     # 编辑php.ini添加:
     [Xdebug]
     zend_extension=xdebug
     xdebug.mode=debug
     xdebug.client_host=127.0.0.1
     xdebug.client_port=9003
     
     # macOS
     brew install php-xdebug
     
     # Linux
     sudo apt install php-xdebug
     ```

2. **Laravel Telescope**
   - 安装:
     ```bash
     composer require laravel/telescope --dev
     php artisan telescope:install
     php artisan migrate
     ```

3. **Vue.js Devtools**
   - 浏览器扩展
   - 用于调试Vue应用

### API测试工具

1. **Postman**
   - API测试和文档工具
   - 可以创建环境变量和测试集合

2. **Insomnia**
   - 轻量级API客户端
   - 支持GraphQL和REST

### 数据库工具

1. **phpMyAdmin**
   - XAMPP自带
   - Web界面管理MySQL

2. **MySQL Workbench**
   - 官方MySQL图形界面工具
   - 支持数据建模和SQL开发

3. **Redis Desktop Manager**
   - Redis图形界面管理工具

## 调试技巧

### PHP调试

1. **使用dd()和dump()**
   ```php
   // 打印变量并终止执行
   dd($variable);
   
   // 打印变量但不终止执行
   dump($variable);
   ```

2. **使用Laravel日志**
   ```php
   // 记录信息
   Log::info('This is an info message');
   
   // 记录错误
   Log::error('This is an error message', ['exception' => $e]);
   ```

3. **使用Xdebug断点调试**
   - 在VSCode或PhpStorm中设置断点
   - 配置调试器
   - 启动调试会话

### Vue.js调试

1. **使用Vue Devtools**
   - 检查组件层次结构
   - 监控状态变化
   - 追踪事件

2. **使用console.log**
   ```javascript
   // 在组件方法中使用
   methods: {
     someMethod() {
       console.log('Method called', this.someData);
     }
   }
   ```

3. **使用断点**
   - 在浏览器开发者工具中设置断点
   - 使用debugger语句
   ```javascript
   methods: {
     complexMethod() {
       debugger; // 代码会在此处暂停执行
       // 后续代码
     }
   }
   ```

### 数据库调试

1. **使用查询日志**
   ```php
   // 在AppServiceProvider中启用查询日志
   public function boot()
   {
       if (app()->environment('local')) {
           DB::listen(function ($query) {
               Log::info(
                   $query->sql,
                   [
                       'bindings' => $query->bindings,
                       'time' => $query->time
                   ]
               );
           });
       }
   }
   ```

2. **使用Laravel Telescope**
   - 访问 `/telescope` 路由
   - 查看请求、查询、日志等信息

## 常见问题解决

### 1. Composer安装依赖失败

**问题**: 运行`composer install`时出现内存不足错误。

**解决方案**:
```bash
# 增加PHP内存限制
php -d memory_limit=-1 composer.phar install
```

### 2. 数据库连接错误

**问题**: 无法连接到MySQL数据库。

**解决方案**:
- 检查MySQL服务是否启动
- 验证数据库凭据是否正确
- 确认数据库用户权限
- 检查防火墙设置

### 3. 权限问题

**问题**: 文件权限错误，无法写入日志或缓存。

**解决方案**:
```bash
# 设置存储目录权限
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 4. 前端构建失败

**问题**: 运行`npm run dev`时出错。

**解决方案**:
- 删除node_modules目录并重新安装
```bash
rm -rf node_modules
npm cache clean --force
npm install
```
- 更新Node.js版本

### 5. Redis连接失败

**问题**: 无法连接到Redis服务器。

**解决方案**:
- 确认Redis服务已启动
- 检查Redis配置
- 验证防火墙设置

## 参考资源

1. [PHP官方文档](https://www.php.net/docs.php)
2. [Laravel文档](https://laravel.com/docs)
3. [Vue.js文档](https://vuejs.org/guide/introduction.html)
4. [MySQL文档](https://dev.mysql.com/doc/)
5. [Redis文档](https://redis.io/documentation)
6. [Composer文档](https://getcomposer.org/doc/)
7. [Node.js文档](https://nodejs.org/en/docs/)

## 版本控制

### Git工作流

推荐使用Git Flow工作流:

1. **主分支**
   - `main`: 生产环境代码
   - `develop`: 开发环境代码

2. **功能分支**
   - 从`develop`分支创建
   - 命名规范: `feature/功能名称`
   - 完成后合并回`develop`

3. **发布分支**
   - 从`develop`分支创建
   - 命名规范: `release/版本号`
   - 完成后合并到`main`和`develop`

4. **热修复分支**
   - 从`main`分支创建
   - 命名规范: `hotfix/问题描述`
   - 完成后合并到`main`和`develop`

### 提交规范

遵循Angular提交规范:

```
<type>(<scope>): <subject>

<body>

<footer>
```

- **type**: 提交类型
  - feat: 新功能
  - fix: 修复bug
  - docs: 文档更新
  - style: 代码格式调整
  - refactor: 重构
  - test: 测试相关
  - chore: 构建过程或辅助工具的变动

- **scope**: 影响范围
  - 可选，表示代码影响的模块

- **subject**: 简短描述
  - 不超过50个字符
  - 使用现在时态
  - 首字母不大写
  - 结尾不加句号

- **body**: 详细描述
  - 可选，说明代码变动的动机

- **footer**: 脚注
  - 可选，用于引用Issue编号等

示例:
```
feat(auth): implement JWT authentication

- Add JWT token generation
- Add token validation middleware
- Update user model for JWT support

Closes #123
```

## 结语

按照本指南配置开发环境后，你应该能够顺利开始企业微信综合管理系统的开发工作。如果遇到问题，请参考上述常见问题解决方案或查阅相关文档。