# API接口文档

## 概述

本文档详细描述了企业微信综合管理系统提供的所有API接口，包括请求方式、参数说明、响应格式等信息。所有接口均采用RESTful风格设计，使用JSON格式进行数据交换。

## 接口认证

### 认证方式

系统采用基于JWT(JSON Web Token)的认证机制。客户端需要在请求头中携带token进行身份验证。

```
Authorization: Bearer {token}
```

### 获取Token

**请求方式**：POST

**接口地址**：`/api/auth/login`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| username | string | 是   | 用户名   |
| password | string | 是   | 密码     |

**响应示例**：

```json
{
    "code": 200,
    "message": "登录成功",
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_in": 86400,
        "token_type": "Bearer"
    }
}
```

## 用户管理接口

### 获取用户列表

**请求方式**：GET

**接口地址**：`/api/users`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| page   | integer | 否   | 页码，默认1 |
| limit  | integer | 否   | 每页条数，默认20 |
| keyword | string | 否   | 搜索关键词 |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "last_page": 5,
        "data": [
            {
                "id": 1,
                "username": "admin",
                "name": "管理员",
                "email": "admin@example.com",
                "phone": "13800138000",
                "status": 1,
                "created_at": "2023-01-01 00:00:00"
            },
            // 更多用户数据...
        ]
    }
}
```

### 获取用户详情

**请求方式**：GET

**接口地址**：`/api/users/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 用户ID   |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "id": 1,
        "username": "admin",
        "name": "管理员",
        "email": "admin@example.com",
        "phone": "13800138000",
        "department": "技术部",
        "position": "系统管理员",
        "status": 1,
        "roles": ["admin", "manager"],
        "created_at": "2023-01-01 00:00:00",
        "updated_at": "2023-01-01 00:00:00"
    }
}
```

### 创建用户

**请求方式**：POST

**接口地址**：`/api/users`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| username | string | 是   | 用户名，长度5-20个字符 |
| password | string | 是   | 密码，长度8-20个字符 |
| name   | string | 是   | 姓名     |
| email  | string | 是   | 邮箱     |
| phone  | string | 是   | 手机号   |
| department | string | 否 | 部门     |
| position | string | 否  | 职位     |
| status | integer | 否  | 状态：1-启用，0-禁用，默认1 |
| role_ids | array | 是  | 角色ID数组 |

**响应示例**：

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 101,
        "username": "newuser",
        "name": "新用户",
        "email": "newuser@example.com",
        "phone": "13900139000",
        "department": "市场部",
        "position": "经理",
        "status": 1,
        "created_at": "2023-01-10 10:00:00"
    }
}
```

### 更新用户

**请求方式**：PUT

**接口地址**：`/api/users/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 用户ID   |

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| name   | string | 否   | 姓名     |
| email  | string | 否   | 邮箱     |
| phone  | string | 否   | 手机号   |
| department | string | 否 | 部门     |
| position | string | 否  | 职位     |
| status | integer | 否  | 状态：1-启用，0-禁用 |
| role_ids | array | 否  | 角色ID数组 |

**响应示例**：

```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 101,
        "username": "newuser",
        "name": "更新后的用户名",
        "email": "updated@example.com",
        "phone": "13900139000",
        "department": "技术部",
        "position": "高级工程师",
        "status": 1,
        "updated_at": "2023-01-15 15:30:00"
    }
}
```

### 删除用户

**请求方式**：DELETE

**接口地址**：`/api/users/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 用户ID   |

**响应示例**：

```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

## 表单管理接口

### 获取表单列表

**请求方式**：GET

**接口地址**：`/api/forms`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| page   | integer | 否   | 页码，默认1 |
| limit  | integer | 否   | 每页条数，默认20 |
| status | integer | 否   | 状态筛选：1-启用，0-禁用 |
| keyword | string | 否   | 搜索关键词 |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3,
        "data": [
            {
                "id": 1,
                "title": "员工入职表单",
                "description": "新员工入职信息采集",
                "status": 1,
                "created_by": "admin",
                "created_at": "2023-01-05 09:00:00",
                "updated_at": "2023-01-05 09:00:00"
            },
            // 更多表单数据...
        ]
    }
}
```

### 获取表单详情

**请求方式**：GET

**接口地址**：`/api/forms/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 表单ID   |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "id": 1,
        "title": "员工入职表单",
        "description": "新员工入职信息采集",
        "status": 1,
        "fields": [
            {
                "id": 1,
                "name": "name",
                "label": "姓名",
                "type": "text",
                "required": true,
                "placeholder": "请输入姓名",
                "options": null,
                "order": 1
            },
            {
                "id": 2,
                "name": "gender",
                "label": "性别",
                "type": "radio",
                "required": true,
                "placeholder": "",
                "options": [
                    {"label": "男", "value": "male"},
                    {"label": "女", "value": "female"}
                ],
                "order": 2
            },
            // 更多字段...
        ],
        "created_by": "admin",
        "created_at": "2023-01-05 09:00:00",
        "updated_at": "2023-01-05 09:00:00"
    }
}
```

### 创建表单

**请求方式**：POST

**接口地址**：`/api/forms`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| title  | string | 是   | 表单标题  |
| description | string | 否 | 表单描述 |
| status | integer | 否  | 状态：1-启用，0-禁用，默认1 |
| fields | array | 是   | 表单字段数组 |

**fields数组元素结构**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| name   | string | 是   | 字段名称，英文标识 |
| label  | string | 是   | 字段标签，显示名称 |
| type   | string | 是   | 字段类型：text, textarea, radio, checkbox, select, date, file等 |
| required | boolean | 否 | 是否必填，默认false |
| placeholder | string | 否 | 占位文本 |
| options | array | 否  | 选项数组，用于radio, checkbox, select类型 |
| order  | integer | 否  | 排序，默认按添加顺序 |

**响应示例**：

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 51,
        "title": "项目申请表单",
        "description": "新项目立项申请",
        "status": 1,
        "fields": [
            // 字段信息...
        ],
        "created_at": "2023-01-20 14:00:00"
    }
}
```

### 更新表单

**请求方式**：PUT

**接口地址**：`/api/forms/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 表单ID   |

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| title  | string | 否   | 表单标题  |
| description | string | 否 | 表单描述 |
| status | integer | 否  | 状态：1-启用，0-禁用 |
| fields | array | 否   | 表单字段数组 |

**响应示例**：

```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 51,
        "title": "更新后的项目申请表单",
        "description": "项目立项申请流程",
        "status": 1,
        "fields": [
            // 更新后的字段信息...
        ],
        "updated_at": "2023-01-25 16:30:00"
    }
}
```

### 删除表单

**请求方式**：DELETE

**接口地址**：`/api/forms/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 表单ID   |

**响应示例**：

```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

## 审批流程接口

### 获取审批流程列表

**请求方式**：GET

**接口地址**：`/api/approval-flows`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| page   | integer | 否   | 页码，默认1 |
| limit  | integer | 否   | 每页条数，默认20 |
| status | integer | 否   | 状态筛选 |
| form_id | integer | 否  | 关联表单ID |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "total": 30,
        "per_page": 20,
        "current_page": 1,
        "last_page": 2,
        "data": [
            {
                "id": 1,
                "name": "请假审批流程",
                "form_id": 2,
                "form_name": "请假申请表",
                "status": 1,
                "node_count": 3,
                "created_at": "2023-02-01 10:00:00"
            },
            // 更多审批流程...
        ]
    }
}
```

### 获取审批流程详情

**请求方式**：GET

**接口地址**：`/api/approval-flows/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 审批流程ID |

**响应示例**：

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        "id": 1,
        "name": "请假审批流程",
        "description": "员工请假审批流程",
        "form_id": 2,
        "form_name": "请假申请表",
        "status": 1,
        "nodes": [
            {
                "id": 1,
                "name": "直接主管审批",
                "type": "approval",
                "approver_type": "role",
                "approver_id": 3,
                "approver_name": "部门主管",
                "order": 1
            },
            {
                "id": 2,
                "name": "人事确认",
                "type": "approval",
                "approver_type": "department",
                "approver_id": 5,
                "approver_name": "人力资源部",
                "order": 2
            },
            {
                "id": 3,
                "name": "总经理审批",
                "type": "approval",
                "approver_type": "user",
                "approver_id": 1,
                "approver_name": "张总",
                "order": 3,
                "conditions": [
                    {
                        "field": "days",
                        "operator": ">",
                        "value": "3"
                    }
                ]
            }
        ],
        "created_at": "2023-02-01 10:00:00",
        "updated_at": "2023-02-01 10:00:00"
    }
}
```

### 创建审批流程

**请求方式**：POST

**接口地址**：`/api/approval-flows`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| name   | string | 是   | 流程名称  |
| description | string | 否 | 流程描述 |
| form_id | integer | 是  | 关联表单ID |
| status | integer | 否  | 状态：1-启用，0-禁用，默认1 |
| nodes  | array | 是   | 审批节点数组 |

**nodes数组元素结构**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| name   | string | 是   | 节点名称  |
| type   | string | 是   | 节点类型：approval-审批，notify-通知 |
| approver_type | string | 是 | 审批人类型：user-用户，role-角色，department-部门 |
| approver_id | integer | 是 | 审批人ID |
| order  | integer | 否  | 节点顺序，默认按添加顺序 |
| conditions | array | 否 | 条件数组，满足条件时节点生效 |

**响应示例**：

```json
{
    "code": 200,
    "message": "创建成功",
    "data": {
        "id": 31,
        "name": "报销审批流程",
        "description": "员工报销审批流程",
        "form_id": 3,
        "form_name": "报销申请表",
        "status": 1,
        "nodes": [
            // 节点信息...
        ],
        "created_at": "2023-02-10 11:00:00"
    }
}
```

### 更新审批流程

**请求方式**：PUT

**接口地址**：`/api/approval-flows/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 审批流程ID |

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| name   | string | 否   | 流程名称  |
| description | string | 否 | 流程描述 |
| status | integer | 否  | 状态：1-启用，0-禁用 |
| nodes  | array | 否   | 审批节点数组 |

**响应示例**：

```json
{
    "code": 200,
    "message": "更新成功",
    "data": {
        "id": 31,
        "name": "更新后的报销审批流程",
        "description": "员工报销审批流程（更新版）",
        "form_id": 3,
        "form_name": "报销申请表",
        "status": 1,
        "nodes": [
            // 更新后的节点信息...
        ],
        "updated_at": "2023-02-15 14:30:00"
    }
}
```

### 删除审批流程

**请求方式**：DELETE

**接口地址**：`/api/approval-flows/{id}`

**路径参数**：

| 参数名 | 类型   | 必填 | 描述     |
|-------|--------|------|----------|
| id    | integer | 是   | 审批流程ID |

**响应示例**：

```json
{
    "code": 200,
    "message": "删除成功",
    "data": null
}
```

## 错误码说明

| 错误码 | 描述                 |
|-------|---------------------|
| 200   | 成功                 |
| 400   | 请求参数错误          |
| 401   | 未授权或token已过期   |
| 403   | 权限不足             |
| 404   | 资源不存在           |
| 422   | 数据验证失败         |
| 500   | 服务器内部错误       |

## 接口调用示例

### cURL示例

```bash
# 登录获取token
curl -X POST \
  http://api.example.com/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{
    "username": "admin",
    "password": "password123"
}'

# 使用token获取用户列表
curl -X GET \
  http://api.example.com/api/users \
  -H 'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
```

### JavaScript示例

```javascript
// 登录获取token
async function login() {
  const response = await fetch('http://api.example.com/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      username: 'admin',
      password: 'password123'
    })
  });
  
  const data = await response.json();
  return data.data.token;
}

// 使用token获取用户列表
async function getUsers(token) {
  const response = await fetch('http://api.example.com/api/users', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return await response.json();
}

// 使用示例
async function example() {
  const token = await login();
  const users = await getUsers(token);
  console.log(users);
}
```

## 注意事项

1. 所有接口调用需要在请求头中携带有效的token
2. 接口返回的数据格式统一为JSON
3. 分页接口默认每页20条数据
4. 文件上传接口需要使用multipart/form-data格式
5. API版本更新会在文档中标注