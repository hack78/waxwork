# 企业微信API对接指南

## 概述

本文档详细描述了企业微信综合管理系统与企业微信API的对接方法，包括认证流程、接口调用、消息推送等内容。通过本指南，开发人员可以快速了解如何将系统与企业微信进行集成。

## 企业微信API简介

企业微信API是腾讯提供的一套开放接口，允许第三方应用与企业微信进行集成，实现企业通讯录管理、消息推送、审批流程等功能。企业微信API主要分为以下几类：

- 通讯录管理
- 应用管理
- 消息推送
- 素材管理
- 身份验证
- OA数据接口（审批、打卡、汇报等）
- 企业支付
- 电子发票

## 开发前准备

### 注册企业微信

1. 访问[企业微信官网](https://work.weixin.qq.com/)
2. 点击"立即注册"，填写企业信息
3. 完成企业认证（可选，但建议完成）

### 创建企业应用

1. 登录[企业微信管理后台](https://work.weixin.qq.com/wework_admin/)
2. 进入"应用管理" > "应用" > "创建应用"
3. 填写应用信息，上传应用Logo
4. 设置应用可见范围
5. 获取应用的AgentId和Secret

### 获取企业ID和应用Secret

1. 企业ID(CorpID)：在"我的企业" > "企业信息"页面获取
2. 应用Secret：在应用详情页面获取

### 配置IP白名单

1. 进入应用详情页
2. 在"开发者接口" > "IP白名单"中添加服务器IP

## 授权认证流程

### 获取access_token

access_token是调用企业微信API的全局唯一凭证，有效期为7200秒（2小时）。

**请求方式**：GET

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/gettoken`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| corpid | string | 是   | 企业ID   |
| corpsecret | string | 是 | 应用Secret |

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "access_token": "accesstoken000001",
    "expires_in": 7200
}
```

**PHP代码示例**：

```php
/**
 * 获取企业微信access_token
 * 
 * @param string $corpId 企业ID
 * @param string $secret 应用Secret
 * @return string|null 成功返回access_token，失败返回null
 */
function getAccessToken($corpId, $secret)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid={$corpId}&corpsecret={$secret}";
    
    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['access_token'])) {
        return $result['access_token'];
    }
    
    return null;
}
```

### access_token管理

由于access_token有效期为2小时，需要合理管理token的获取和刷新。

**建议实现方式**：

1. 使用Redis缓存access_token
2. 设置过期时间为7000秒（比实际过期时间短）
3. 请求前检查缓存，如果不存在则重新获取

**PHP代码示例**：

```php
/**
 * 获取或刷新access_token
 * 
 * @param string $corpId 企业ID
 * @param string $secret 应用Secret
 * @return string|null 成功返回access_token，失败返回null
 */
function getOrRefreshAccessToken($corpId, $secret)
{
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    
    $cacheKey = "wework_access_token_{$corpId}";
    $accessToken = $redis->get($cacheKey);
    
    if (!$accessToken) {
        $accessToken = getAccessToken($corpId, $secret);
        if ($accessToken) {
            // 设置过期时间为7000秒（比实际过期时间短）
            $redis->setex($cacheKey, 7000, $accessToken);
        }
    }
    
    return $accessToken;
}
```

## 通讯录管理

### 获取部门列表

**请求方式**：GET

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/department/list`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| access_token | string | 是 | 调用接口凭证 |
| id | integer | 否 | 部门id，获取指定部门及其下的子部门 |

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "department": [
        {
            "id": 1,
            "name": "公司",
            "parentid": 0,
            "order": 1
        },
        {
            "id": 2,
            "name": "技术部",
            "parentid": 1,
            "order": 1
        }
    ]
}
```

**PHP代码示例**：

```php
/**
 * 获取企业微信部门列表
 * 
 * @param string $accessToken 调用接口凭证
 * @param int|null $id 部门ID，可选
 * @return array|null 成功返回部门列表，失败返回null
 */
function getDepartmentList($accessToken, $id = null)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token={$accessToken}";
    if ($id !== null) {
        $url .= "&id={$id}";
    }
    
    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['department'])) {
        return $result['department'];
    }
    
    return null;
}
```

### 获取部门成员

**请求方式**：GET

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/user/simplelist`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| access_token | string | 是 | 调用接口凭证 |
| department_id | integer | 是 | 获取的部门id |
| fetch_child | integer | 否 | 是否递归获取子部门下面的成员：1-递归获取，0-只获取本部门 |

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "userlist": [
        {
            "userid": "zhangsan",
            "name": "张三",
            "department": [1, 2]
        },
        {
            "userid": "lisi",
            "name": "李四",
            "department": [1]
        }
    ]
}
```

**PHP代码示例**：

```php
/**
 * 获取部门成员
 * 
 * @param string $accessToken 调用接口凭证
 * @param int $departmentId 部门ID
 * @param bool $fetchChild 是否递归获取子部门成员
 * @return array|null 成功返回成员列表，失败返回null
 */
function getDepartmentUsers($accessToken, $departmentId, $fetchChild = false)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token={$accessToken}&department_id={$departmentId}";
    if ($fetchChild) {
        $url .= "&fetch_child=1";
    }
    
    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['userlist'])) {
        return $result['userlist'];
    }
    
    return null;
}
```

### 获取成员详情

**请求方式**：GET

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/user/get`

**请求参数**：

| 参数名   | 类型   | 必填 | 描述     |
|--------|--------|------|----------|
| access_token | string | 是 | 调用接口凭证 |
| userid | string | 是 | 成员UserID |

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "userid": "zhangsan",
    "name": "张三",
    "department": [1, 2],
    "position": "产品经理",
    "mobile": "13800000000",
    "gender": "1",
    "email": "zhangsan@example.com",
    "avatar": "http://wx.qlogo.cn/mmhead/..."
}
```

**PHP代码示例**：

```php
/**
 * 获取成员详情
 * 
 * @param string $accessToken 调用接口凭证
 * @param string $userId 成员UserID
 * @return array|null 成功返回成员信息，失败返回null
 */
function getUserInfo($accessToken, $userId)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token={$accessToken}&userid={$userId}";
    
    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['userid'])) {
        return $result;
    }
    
    return null;
}
```

## 消息推送

### 发送应用消息

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=ACCESS_TOKEN`

**请求参数**：

```json
{
    "touser": "UserID1|UserID2|UserID3",
    "toparty": "PartyID1|PartyID2",
    "totag": "TagID1|TagID2",
    "msgtype": "text",
    "agentid": 1,
    "text": {
        "content": "你好，这是一条测试消息"
    },
    "safe": 0,
    "enable_id_trans": 0,
    "enable_duplicate_check": 0,
    "duplicate_check_interval": 1800
}
```

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "msgid": "MSGID",
    "response_code": "0"
}
```

**PHP代码示例**：

```php
/**
 * 发送文本消息
 * 
 * @param string $accessToken 调用接口凭证
 * @param string|array $toUser 接收消息的用户，多个用户用'|'分隔，也可以是数组
 * @param int $agentId 应用ID
 * @param string $content 消息内容
 * @return bool 成功返回true，失败返回false
 */
function sendTextMessage($accessToken, $toUser, $agentId, $content)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$accessToken}";
    
    if (is_array($toUser)) {
        $toUser = implode('|', $toUser);
    }
    
    $data = [
        'touser' => $toUser,
        'msgtype' => 'text',
        'agentid' => $agentId,
        'text' => [
            'content' => $content
        ],
        'safe' => 0,
        'enable_duplicate_check' => 1,
        'duplicate_check_interval' => 1800
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['errcode']) && $result['errcode'] === 0;
}
```

### 发送卡片消息

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=ACCESS_TOKEN`

**请求参数**：

```json
{
    "touser": "UserID1|UserID2|UserID3",
    "msgtype": "textcard",
    "agentid": 1,
    "textcard": {
        "title": "领奖通知",
        "description": "<div class=\"gray\">2016年9月26日</div><div class=\"normal\">恭喜你抽中iPhone 7一台，领奖码：xxxx</div><div class=\"highlight\">请于2016年10月10日前领取</div>",
        "url": "URL",
        "btntxt": "更多"
    },
    "enable_id_trans": 0,
    "enable_duplicate_check": 0,
    "duplicate_check_interval": 1800
}
```

**PHP代码示例**：

```php
/**
 * 发送卡片消息
 * 
 * @param string $accessToken 调用接口凭证
 * @param string|array $toUser 接收消息的用户，多个用户用'|'分隔，也可以是数组
 * @param int $agentId 应用ID
 * @param string $title 标题
 * @param string $description 描述
 * @param string $url 点击后跳转的链接
 * @param string $btnTxt 按钮文字，默认为"详情"
 * @return bool 成功返回true，失败返回false
 */
function sendCardMessage($accessToken, $toUser, $agentId, $title, $description, $url, $btnTxt = "详情")
{
    $apiUrl = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$accessToken}";
    
    if (is_array($toUser)) {
        $toUser = implode('|', $toUser);
    }
    
    $data = [
        'touser' => $toUser,
        'msgtype' => 'textcard',
        'agentid' => $agentId,
        'textcard' => [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'btntxt' => $btnTxt
        ],
        'enable_duplicate_check' => 1,
        'duplicate_check_interval' => 1800
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['errcode']) && $result['errcode'] === 0;
}
```

## OA数据接口

### 获取审批模板

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/oa/gettemplatedetail?access_token=ACCESS_TOKEN`

**请求参数**：

```json
{
    "template_id": "模板ID"
}
```

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "template": {
        "template_name": "请假申请",
        "template_id": "3Tka1eD6v6JfzhDMqPd3aMkFdxqtJMc2ZRioeFXkaaa",
        "controls": [
            {
                "control": "Text",
                "id": "Text-15111111111",
                "title": [
                    {
                        "text": "请假类型",
                        "lang": "zh_CN"
                    }
                ],
                "placeholder": [
                    {
                        "text": "请输入",
                        "lang": "zh_CN"
                    }
                ],
                "require": 1
            }
        ]
    }
}
```

**PHP代码示例**：

```php
/**
 * 获取审批模板详情
 * 
 * @param string $accessToken 调用接口凭证
 * @param string $templateId 模板ID
 * @return array|null 成功返回模板详情，失败返回null
 */
function getApprovalTemplateDetail($accessToken, $templateId)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/oa/gettemplatedetail?access_token={$accessToken}";
    
    $data = [
        'template_id' => $templateId
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['template'])) {
        return $result['template'];
    }
    
    return null;
}
```

### 提交审批申请

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/oa/applyevent?access_token=ACCESS_TOKEN`

**请求参数**：

```json
{
    "creator_userid": "WangXiaoMing",
    "template_id": "3Tka1eD6v6JfzhDMqPd3aMkFdxqtJMc2ZRioeFXkaaa",
    "use_template_approver": 1,
    "approver": [
        {
            "attr": 2,
            "userid": ["WuJunJie","WangXiaoMing"]
        },
        {
            "attr": 1,
            "userid": ["LiuXiaoGang"]
        }
    ],
    "notifyer": ["WuJunJie","WangXiaoMing"],
    "notify_type": 1,
    "apply_data": {
        "contents": [
            {
                "control": "Text",
                "id": "Text-15111111111",
                "value": {
                    "text": "病假"
                }
            }
        ]
    },
    "summary_list": [
        {
            "summary_info": [
                {
                    "text": "请假时间: ",
                    "lang": "zh_CN"
                }
            ]
        },
        {
            "summary_info": [
                {
                    "text": "2016-12-01至2016-12-01 ",
                    "lang": "zh_CN"
                }
            ]
        },
        {
            "summary_info": [
                {
                    "text": "请假事由：",
                    "lang": "zh_CN"
                }
            ]
        },
        {
            "summary_info": [
                {
                    "text": "发烧了",
                    "lang": "zh_CN"
                }
            ]
        }
    ]
}
```

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "sp_no": "201709270001"
}
```

**PHP代码示例**：

```php
/**
 * 提交审批申请
 * 
 * @param string $accessToken 调用接口凭证
 * @param string $creatorUserId 申请人userid
 * @param string $templateId 模板ID
 * @param array $applyData 审批申请数据
 * @param array $summaryList 摘要信息
 * @return string|null 成功返回审批单号，失败返回null
 */
function submitApproval($accessToken, $creatorUserId, $templateId, $applyData, $summaryList)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/oa/applyevent?access_token={$accessToken}";
    
    $data = [
        'creator_userid' => $creatorUserId,
        'template_id' => $templateId,
        'use_template_approver' => 1,
        'notify_type' => 1,
        'apply_data' => [
            'contents' => $applyData
        ],
        'summary_list' => $summaryList
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['sp_no'])) {
        return $result['sp_no'];
    }
    
    return null;
}
```

### 获取审批申请详情

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/oa/getapprovaldetail?access_token=ACCESS_TOKEN`

**请求参数**：

```json
{
    "sp_no": "201709270001"
}
```

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok",
    "info": {
        "sp_no": "201709270001",
        "sp_name": "请假申请",
        "sp_status": 1,
        "template_id": "3Tka1eD6v6JfzhDMqPd3aMkFdxqtJMc2ZRioeFXkaaa",
        "apply_time": 1509084738,
        "applyer": {
            "userid": "WangXiaoMing",
            "partyid": 1
        },
        "sp_record": [
            {
                "sp_status": 1,
                "approverattr": 1,
                "details": [
                    {
                        "approver": {
                            "userid": "WuJunJie"
                        },
                        "speech": "同意",
                        "sp_status": 1,
                        "sptime": 1509084795
                    }
                ]
            },
            {
                "sp_status": 1,
                "approverattr": 1,
                "details": [
                    {
                        "approver": {
                            "userid": "LiuXiaoGang"
                        },
                        "speech": "",
                        "sp_status": 1,
                        "sptime": 1509084795
                    }
                ]
            }
        ],
        "notifyer": [
            {
                "userid": "WuJunJie"
            },
            {
                "userid": "WangXiaoMing"
            }
        ],
        "apply_data": {
            "contents": [
                {
                    "control": "Text",
                    "id": "Text-15111111111",
                    "value": {
                        "text": "病假"
                    }
                }
            ]
        },
        "comments": [
            {
                "commentUserInfo": {
                    "userid": "WuJunJie"
                },
                "commenttime": 1509084795,
                "commentcontent": "已经看到，情况属实"
            }
        ]
    }
}
```

**PHP代码示例**：

```php
/**
 * 获取审批申请详情
 * 
 * @param string $accessToken 调用接口凭证
 * @param string $spNo 审批单号
 * @return array|null 成功返回审批详情，失败返回null
 */
function getApprovalDetail($accessToken, $spNo)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/oa/getapprovaldetail?access_token={$accessToken}";
    
    $data = [
        'sp_no' => $spNo
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $result = json_decode($response, true);
    if (isset($result['info'])) {
        return $result['info'];
    }
    
    return null;
}
```

## 消息回调与事件处理

### 配置接收消息服务器

1. 登录企业微信管理后台
2. 进入应用详情页
3. 点击"接收消息"设置
4. 填写接收消息的URL、Token和EncodingAESKey
5. 启用接收消息

### 验证URL有效性

企业微信在配置接收消息URL时，会发送一个GET请求进行验证。

**PHP代码示例**：

```php
/**
 * 验证URL有效性
 * 
 * @param string $token 配置的Token
 * @param string $encodingAesKey 配置的EncodingAESKey
 * @param string $corpId 企业ID
 * @return void
 */
function verifyURL($token, $encodingAesKey, $corpId)
{
    // 企业微信加密类库，需要引入企业微信提供的库文件
    require_once "WXBizMsgCrypt.php";
    
    // 获取URL中的参数
    $msg_signature = $_GET['msg_signature'];
    $timestamp = $_GET['timestamp'];
    $nonce = $_GET['nonce'];
    $echostr = $_GET['echostr'];
    
    // 实例化加密类
    $wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, $corpId);
    
    // 解密echostr
    $sEchoStr = "";
    $errCode = $wxcpt->VerifyURL($msg_signature, $timestamp, $nonce, $echostr, $sEchoStr);
    
    if ($errCode == 0) {
        // URL验证成功，返回解密后的echostr
        echo $sEchoStr;
    } else {
        // URL验证失败
        echo "";
    }
}
```

### 接收消息和事件

企业微信会将消息和事件以XML格式POST到配置的URL。

**PHP代码示例**：

```php
/**
 * 接收消息和事件
 * 
 * @param string $token 配置的Token
 * @param string $encodingAesKey 配置的EncodingAESKey
 * @param string $corpId 企业ID
 * @return array|null 成功返回消息数组，失败返回null
 */
function receiveMessage($token, $encodingAesKey, $corpId)
{
    // 企业微信加密类库，需要引入企业微信提供的库文件
    require_once "WXBizMsgCrypt.php";
    
    // 获取POST数据
    $postData = file_get_contents("php://input");
    if (empty($postData)) {
        return null;
    }
    
    // 获取URL中的参数
    $msg_signature = $_GET['msg_signature'];
    $timestamp = $_GET['timestamp'];
    $nonce = $_GET['nonce'];
    
    // 实例化加密类
    $wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, $corpId);
    
    // 解密消息
    $msg = "";
    $errCode = $wxcpt->DecryptMsg($msg_signature, $timestamp, $nonce, $postData, $msg);
    
    if ($errCode == 0) {
        // 解析XML
        $xml = simplexml_load_string($msg);
        if ($xml === false) {
            return null;
        }
        
        // 转换为数组
        $message = json_decode(json_encode($xml), true);
        return $message;
    }
    
    return null;
}

/**
 * 处理接收到的消息
 * 
 * @param array $message 接收到的消息
 * @return string|null 回复的消息，不需要回复则返回null
 */
function handleMessage($message)
{
    if (!isset($message['MsgType'])) {
        return null;
    }
    
    switch ($message['MsgType']) {
        case 'text':
            // 处理文本消息
            return handleTextMessage($message);
        case 'image':
            // 处理图片消息
            return handleImageMessage($message);
        case 'voice':
            // 处理语音消息
            return handleVoiceMessage($message);
        case 'video':
            // 处理视频消息
            return handleVideoMessage($message);
        case 'location':
            // 处理位置消息
            return handleLocationMessage($message);
        case 'link':
            // 处理链接消息
            return handleLinkMessage($message);
        case 'event':
            // 处理事件消息
            return handleEventMessage($message);
        default:
            return null;
    }
}

/**
 * 处理事件消息
 * 
 * @param array $message 接收到的事件消息
 * @return string|null 回复的消息，不需要回复则返回null
 */
function handleEventMessage($message)
{
    if (!isset($message['Event'])) {
        return null;
    }
    
    switch ($message['Event']) {
        case 'subscribe':
            // 处理关注事件
            return "感谢关注！";
        case 'unsubscribe':
            // 处理取消关注事件
            return null;
        case 'CLICK':
            // 处理菜单点击事件
            return handleMenuClick($message);
        case 'VIEW':
            // 处理菜单跳转事件
            return null;
        case 'sys_approval_change':
            // 处理审批状态变更事件
            return handleApprovalChange($message);
        default:
            return null;
    }
}

/**
 * 回复消息
 * 
 * @param string $toUser 接收方帐号
 * @param string $fromUser 发送方帐号
 * @param string $content 回复的消息内容
 * @param string $token 配置的Token
 * @param string $encodingAesKey 配置的EncodingAESKey
 * @param string $corpId 企业ID
 * @return string 加密后的XML消息
 */
function replyTextMessage($toUser, $fromUser, $content, $token, $encodingAesKey, $corpId)
{
    // 构造回复消息
    $replyMsg = "<xml>
        <ToUserName><![CDATA[{$toUser}]]></ToUserName>
        <FromUserName><![CDATA[{$fromUser}]]></FromUserName>
        <CreateTime>" . time() . "</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[{$content}]]></Content>
    </xml>";
    
    // 企业微信加密类库，需要引入企业微信提供的库文件
    require_once "WXBizMsgCrypt.php";
    
    // 实例化加密类
    $wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, $corpId);
    
    // 加密回复消息
    $encryptMsg = "";
    $timestamp = time();
    $nonce = substr(md5(mt_rand()), 0, 10);
    $errCode = $wxcpt->EncryptMsg($replyMsg, $timestamp, $nonce, $encryptMsg);
    
    if ($errCode == 0) {
        return $encryptMsg;
    }
    
    return "";
}

## 自定义菜单

### 创建菜单

**请求方式**：POST

**接口地址**：`https://qyapi.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN&agentid=AGENTID`

**请求参数**：

```json
{
    "button": [
        {
            "type": "click",
            "name": "今日工作",
            "key": "V1001_TODAY_WORK"
        },
        {
            "name": "菜单",
            "sub_button": [
                {
                    "type": "view",
                    "name": "我的审批",
                    "url": "http://work.weixin.qq.com/wework_admin/frame_apps"
                },
                {
                    "type": "click",
                    "name": "帮助",
                    "key": "V1001_HELP"
                }
            ]
        }
    ]
}
```

**响应示例**：

```json
{
    "errcode": 0,
    "errmsg": "ok"
}
```

**PHP代码示例**：

```php
/**
 * 创建自定义菜单
 * 
 * @param string $accessToken 调用接口凭证
 * @param int $agentId 应用ID
 * @param array $menuData 菜单数据
 * @return bool 成功返回true，失败返回false
 */
function createMenu($accessToken, $agentId, $menuData)
{
    $url = "https://qyapi.weixin.qq.com/cgi-bin/menu/create?access_token={$accessToken}&agentid={$agentId}";
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($menuData, JSON_UNESCAPED_UNICODE)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['errcode']) && $result['errcode'] === 0;
}
```

## 安全与最佳实践

### 数据安全

1. **Secret保护**
   - 不要在客户端存储Secret
   - 不要将Secret硬编码在代码中
   - 使用环境变量或配置文件存储Secret

2. **access_token管理**
   - 使用缓存存储access_token
   - 设置合理的过期时间
   - 避免频繁获取access_token

3. **数据加密**
   - 使用HTTPS协议
   - 敏感数据加密存储
   - 使用企业微信提供的加密解密库

### 性能优化

1. **减少API调用**
   - 合理缓存API返回结果
   - 批量处理数据
   - 避免重复调用

2. **异步处理**
   - 使用队列处理消息发送
   - 使用异步任务处理耗时操作
   - 设置合理的超时时间

3. **错误处理**
   - 实现完善的日志记录
   - 设置重试机制
   - 监控API调用状态

### 开发建议

1. **模块化设计**
   - 将企业微信API封装为独立模块
   - 使用依赖注入管理配置
   - 遵循单一职责原则

2. **测试**
   - 编写单元测试
   - 使用模拟数据进行测试
   - 在测试环境验证功能

3. **文档**
   - 记录API调用方式
   - 维护接口变更日志
   - 提供使用示例

## 常见问题解答

### 1. access_token获取失败

**问题**：调用接口获取access_token时返回错误。

**解决方案**：
- 检查corpid和secret是否正确
- 确认应用是否已启用
- 检查网络连接是否正常
- 查看企业微信后台是否有异常提示

### 2. 消息发送失败

**问题**：发送消息时返回错误。

**解决方案**：
- 检查access_token是否有效
- 确认接收人是否在可见范围内
- 检查消息格式是否正确
- 确认应用是否有发送消息权限

### 3. 回调URL验证失败

**问题**：配置接收消息URL时验证失败。

**解决方案**：
- 确认URL可以正常访问
- 检查Token和EncodingAESKey是否正确
- 确认解密逻辑是否正确
- 检查服务器时间是否准确

### 4. 获取部门/成员信息为空

**问题**：调用接口获取部门或成员信息时返回空数据。

**解决方案**：
- 确认应用可见范围是否包含目标部门/成员
- 检查应用权限是否包含通讯录权限
- 确认企业通讯录是否已同步

### 5. 审批流程无法获取

**问题**：无法获取审批流程数据。

**解决方案**：
- 确认应用是否有审批数据权限
- 检查审批应用是否已启用
- 确认审批模板是否存在
- 检查审批单号是否正确

## 参考资源

1. [企业微信开发文档](https://work.weixin.qq.com/api/doc)
2. [企业微信API调试工具](https://work.weixin.qq.com/api/devtools/devtool.php)
3. [企业微信开发者社区](https://developers.weixin.qq.com/community/wecom)
4. [PHP SDK for 企业微信](https://github.com/wechatpay-apiv3/wechatpay-php)

## 附录

### 错误码说明

| 错误码 | 说明                 | 解决方案                |
|-------|---------------------|------------------------|
| 0     | 请求成功             | -                      |
| 40001 | 不合法的secret参数    | 检查secret是否正确       |
| 40014 | 不合法的access_token | 重新获取access_token    |
| 40056 | 不合法的agentid      | 检查agentid是否正确      |
| 40096 | 不合法的外部联系人userid | 检查外部联系人userid是否正确 |
| 41001 | 缺少access_token参数 | 请求中加入access_token  |
| 42001 | access_token已过期  | 重新获取access_token    |
| 60011 | 用户不在权限范围内     | 检查应用可见范围          |

### 常用接口汇总

| 接口名称 | 接口地址 | 说明 |
|---------|---------|------|
| 获取access_token | /cgi-bin/gettoken | 获取调用接口凭证 |
| 获取部门列表 | /cgi-bin/department/list | 获取企业部门列表 |
| 获取部门成员 | /cgi-bin/user/simplelist | 获取部门成员列表 |
| 获取成员详情 | /cgi-bin/user/get | 获取成员详细信息 |
| 发送应用消息 | /cgi-bin/message/send | 发送应用消息 |
| 创建自定义菜单 | /cgi-bin/menu/create | 创建应用菜单 |
| 获取审批模板 | /cgi-bin/oa/gettemplatedetail | 获取审批模板详情 |
| 提交审批申请 | /cgi-bin/oa/applyevent | 提交审批申请 |
| 获取审批详情 | /cgi-bin/oa/getapprovaldetail | 获取审批申请详情 |