# 数据库设计文档

## 概述

本文档详细描述了企业微信综合管理系统的数据库设计，包括表结构、关系、索引等信息。系统使用MySQL 8.0作为数据库管理系统。

## 数据库配置

- 字符集：utf8mb4
- 排序规则：utf8mb4_unicode_ci
- 存储引擎：InnoDB

## 表结构设计

### 用户表 (users)

存储系统用户信息。

```sql
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `department` varchar(50) DEFAULT NULL COMMENT '部门',
  `position` varchar(50) DEFAULT NULL COMMENT '职位',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `wework_userid` varchar(100) DEFAULT NULL COMMENT '企业微信用户ID',
  `last_login_at` datetime DEFAULT NULL COMMENT '最后登录时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_department` (`department`),
  KEY `idx_wework_userid` (`wework_userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

### 角色表 (roles)

定义系统角色。

```sql
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色编码',
  `description` varchar(200) DEFAULT NULL COMMENT '角色描述',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';
```

### 用户角色关联表 (user_roles)

用户和角色的多对多关联。

```sql
CREATE TABLE `user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role_id`),
  KEY `idx_role_id` (`role_id`),
  CONSTRAINT `fk_ur_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';
```

### 权限表 (permissions)

系统权限定义。

```sql
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `name` varchar(50) NOT NULL COMMENT '权限名称',
  `code` varchar(50) NOT NULL COMMENT '权限编码',
  `description` varchar(200) DEFAULT NULL COMMENT '权限描述',
  `type` varchar(20) NOT NULL COMMENT '权限类型：menu-菜单，button-按钮，api-接口',
  `parent_id` bigint unsigned DEFAULT NULL COMMENT '父级权限ID',
  `path` varchar(200) DEFAULT NULL COMMENT '权限路径',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标',
  `sort` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限表';
```

### 角色权限关联表 (role_permissions)

角色和权限的多对多关联。

```sql
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `role_id` bigint unsigned NOT NULL COMMENT '角色ID',
  `permission_id` bigint unsigned NOT NULL COMMENT '权限ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `idx_permission_id` (`permission_id`),
  CONSTRAINT `fk_rp_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限关联表';
```

### 表单表 (forms)

存储系统中的表单定义。

```sql
CREATE TABLE `forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '表单ID',
  `title` varchar(100) NOT NULL COMMENT '表单标题',
  `description` varchar(500) DEFAULT NULL COMMENT '表单描述',
  `type` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '表单类型：normal-普通表单，approval-审批表单',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_by` bigint unsigned NOT NULL COMMENT '创建人ID',
  `updated_by` bigint unsigned NOT NULL COMMENT '更新人ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_forms_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_forms_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单表';
```

### 表单字段表 (form_fields)

存储表单的字段定义。

```sql
CREATE TABLE `form_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '字段ID',
  `form_id` bigint unsigned NOT NULL COMMENT '表单ID',
  `name` varchar(50) NOT NULL COMMENT '字段名称',
  `label` varchar(50) NOT NULL COMMENT '字段标签',
  `type` varchar(20) NOT NULL COMMENT '字段类型：text,textarea,radio,checkbox,select,date,file等',
  `required` tinyint NOT NULL DEFAULT '0' COMMENT '是否必填：1-是，0-否',
  `placeholder` varchar(100) DEFAULT NULL COMMENT '占位文本',
  `default_value` varchar(500) DEFAULT NULL COMMENT '默认值',
  `options` json DEFAULT NULL COMMENT '选项配置，用于radio,checkbox,select类型',
  `validation_rules` json DEFAULT NULL COMMENT '验证规则',
  `order` int NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ff_form_id` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单字段表';
```

### 表单数据表 (form_data)

存储表单提交的数据。

```sql
CREATE TABLE `form_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '数据ID',
  `form_id` bigint unsigned NOT NULL COMMENT '表单ID',
  `data` json NOT NULL COMMENT '表单数据',
  `status` varchar(20) NOT NULL DEFAULT 'submitted' COMMENT '状态：submitted-已提交，processing-处理中，completed-已完成，rejected-已拒绝',
  `submitted_by` bigint unsigned NOT NULL COMMENT '提交人ID',
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_status` (`status`),
  KEY `idx_submitted_by` (`submitted_by`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `fk_fd_form_id` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `fk_fd_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='表单数据表';
```

### 审批流程表 (approval_flows)

定义审批流程。

```sql
CREATE TABLE `approval_flows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '流程ID',
  `name` varchar(100) NOT NULL COMMENT '流程名称',
  `description` varchar(500) DEFAULT NULL COMMENT '流程描述',
  `form_id` bigint unsigned NOT NULL COMMENT '关联表单ID',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_by` bigint unsigned NOT NULL COMMENT '创建人ID',
  `updated_by` bigint unsigned NOT NULL COMMENT '更新人ID',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_af_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_af_form_id` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`),
  CONSTRAINT `fk_af_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批流程表';
```

### 审批节点表 (approval_nodes)

定义审批流程中的节点。

```sql
CREATE TABLE `approval_nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '节点ID',
  `flow_id` bigint unsigned NOT NULL COMMENT '流程ID',
  `name` varchar(50) NOT NULL COMMENT '节点名称',
  `type` varchar(20) NOT NULL COMMENT '节点类型：approval-审批，notify-通知',
  `approver_type` varchar(20) NOT NULL COMMENT '审批人类型：user-用户，role-角色，department-部门',
  `approver_id` bigint unsigned NOT NULL COMMENT '审批人ID',
  `conditions` json DEFAULT NULL COMMENT '条件配置',
  `order` int NOT NULL DEFAULT '0' COMMENT '节点顺序',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '状态：1-启用，0-禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_flow_id` (`flow_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_an_flow_id` FOREIGN KEY (`flow_id`) REFERENCES `approval_flows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批节点表';
```

### 审批记录表 (approval_records)

记录审批流程的执行情况。

```sql
CREATE TABLE `approval_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `flow_id` bigint unsigned NOT NULL COMMENT '流程ID',
  `node_id` bigint unsigned NOT NULL COMMENT '节点ID',
  `form_data_id` bigint unsigned NOT NULL COMMENT '表单数据ID',
  `approver_id` bigint unsigned NOT NULL COMMENT '审批人ID',
  `status` varchar(20) NOT NULL COMMENT '状态：pending-待审批，approved-已通过，rejected-已拒绝',
  `comment` text DEFAULT NULL COMMENT '审批意见',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_flow_id` (`flow_id`),
  KEY `idx_node_id` (`node_id`),
  KEY `idx_form_data_id` (`form_data_id`),
  KEY `idx_approver_id` (`approver_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ar_approver_id` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_ar_flow_id` FOREIGN KEY (`flow_id`) REFERENCES `approval_flows` (`id`),
  CONSTRAINT `fk_ar_form_data_id` FOREIGN KEY (`form_data_id`) REFERENCES `form_data` (`id`),
  CONSTRAINT `fk_ar_node_id` FOREIGN KEY (`node_id`) REFERENCES `approval_nodes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审批记录表';
```

### 系统配置表 (system_configs)

存储系统配置信息。

```sql
CREATE TABLE `system_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `name` varchar(50) NOT NULL COMMENT '配置名称',
  `key` varchar(50) NOT NULL COMMENT '配置键',
  `value` text NOT NULL COMMENT '配置值',
  `type` varchar(20) NOT NULL DEFAULT 'string' COMMENT '值类型：string,number,boolean,json',
  `description` varchar(200) DEFAULT NULL COMMENT '配置描述',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';
```

## 表关系说明

1. 用户-角色：多对多关系
   - 通过user_roles表关联
   - 一个用户可以有多个角色
   - 一个角色可以分配给多个用户

2. 角色-权限：多对多关系
   - 通过role_permissions表关联
   - 一个角色可以有多个权限
   - 一个权限可以分配给多个角色

3. 表单-字段：一对多关系
   - 一个表单可以包含多个字段
   - 每个字段属于一个表单

4. 表单-数据：一对多关系
   - 一个表单可以有多条提交数据
   - 每条数据属于一个表单

5. 审批流程-节点：一对多关系
   - 一个审批流程包含多个节点
   - 每个节点属于一个审批流程

6. 审批流程-表单：一对一关系
   - 一个审批流程关联一个表单
   - 一个表单可以关联一个审批流程

## 索引设计说明

1. 主键索引
   - 所有表都使用自增的bigint类型作为主键
   - 主键名统一为`id`

2. 唯一索引
   - 用户表：用户名、邮箱、手机号
   - 角色表：角色编码
   - 权限表：权限编码
   - 系统配置表：配置键

3. 外键索引
   - 所有外键关系都建立了相应的索引
   - 外键名称统一使用`fk_`前缀

4. 普通索引
   - 状态字段：用于状态筛选
   - 创建时间：用于时间范围查询
   - 类型字段：用于类型筛选
   - 其他经常用于查询条件的字段

## 示例数据

### 角色数据

```sql
INSERT INTO `roles` (`name`, `code`, `description`) VALUES
('系统管理员', 'admin', '系统最高权限角色'),
('部门主管', 'manager', '部门管理角色'),
('普通员工', 'employee', '基础员工角色');
```

### 权限数据

```sql
INSERT INTO `permissions` (`name`, `code`, `type`, `path`) VALUES
('用户管理', 'user:manage', 'menu', '/user'),
('角色管理', 'role:manage', 'menu', '/role'),
('表单管理', 'form:manage', 'menu', '/form'),
('审批管理', 'approval:manage', 'menu', '/approval');
```

### 系统配置数据

```sql
INSERT INTO `system_configs` (`name`, `key`, `value`, `type`, `description`) VALUES
('系统名称', 'system_name', '企业微信综合管理系统', 'string', '系统显示名称'),
('企业微信CorpID', 'wework_corpid', 'your_corpid', 'string', '企业微信企业ID'),
('企业微信Secret', 'wework_secret', 'your_secret', 'string', '企业微信应用Secret');
```

## 数据库维护建议

1. 定期备份
   - 每日全量备份
   - 实时binlog备份
   - 定期测试恢复流程

2. 性能优化
   - 定期检查慢查询日志
   - 优化高频查询的索引
   - 适时进行表分区

3. 数据清理
   - 定期清理历史数据
   - 归档不常用数据
   - 维护表统计信息

4. 安全建议
   - 定期更新数据库密码
   - 限制数据库访问IP
   - 加密敏感信息

## 版本控制

数据库结构变更需要通过迁移文件进行版本控制，确保开发环境和生产环境的数据库结构一致。

### 迁移文件命名规范

```
YYYYMMDDHHMMSS_description.sql
```

例如：
```
20240117100000_create_initial_tables.sql
20240117100100_add_index_to_users.sql
```

### 迁移文件目录结构

```
database/
  ├── migrations/           # 迁移文件
  ├── seeds/               # 数据填充
  └── backups/             # 备份文件
```

## 注意事项

1. 字符集统一使用utf8mb4
2. 所有表必须包含created_at和updated_at字段
3. 删除操作建议使用软删除
4. 敏感数据需要加密存储
5. 大字段建议单独建表
6. 需要考虑分表分库的可能性