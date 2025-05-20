<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WeworkService
{
    protected $corpId;
    protected $secret;
    protected $agentId;
    protected $token;
    protected $aesKey;
    protected $httpClient;

    public function __construct()
    {
        $this->corpId = config('wework.corpid');
        $this->secret = config('wework.secret');
        $this->agentId = config('wework.agent_id');
        $this->token = config('wework.token');
        $this->aesKey = config('wework.aes_key');
        $this->httpClient = new Client([
            'base_uri' => 'https://qyapi.weixin.qq.com/cgi-bin/',
            'timeout' => 5.0,
        ]);
    }

    /**
     * 获取access_token
     */
    public function getAccessToken()
    {
        return Cache::remember('wework_access_token', 7000, function () {
            $response = $this->httpClient->get('gettoken', [
                'query' => [
                    'corpid' => $this->corpId,
                    'corpsecret' => $this->secret,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                return $data['access_token'];
            }

            throw new \Exception('Failed to get access_token: ' . json_encode($data));
        });
    }

    /**
     * 验证回调URL
     */
    public function verifyURL($msgSignature, $timestamp, $nonce, $echostr)
    {
        // 这里需要实现企业微信提供的加解密逻辑
        // 实际项目中应该使用企业微信提供的SDK
        
        // 模拟验证成功，返回解密后的echostr
        return $echostr;
    }

    /**
     * 解密消息
     */
    public function decryptMessage($msgSignature, $timestamp, $nonce, $encryptMsg)
    {
        // 这里需要实现企业微信提供的加解密逻辑
        // 实际项目中应该使用企业微信提供的SDK
        
        // 模拟解密成功，返回消息数组
        return [
            'ToUserName' => 'toUser',
            'FromUserName' => 'fromUser',
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => 'This is a test message',
        ];
    }

    /**
     * 获取用户信息
     */
    public function getUserInfoByCode($code)
    {
        $accessToken = $this->getAccessToken();

        $response = $this->httpClient->get('user/getuserinfo', [
            'query' => [
                'access_token' => $accessToken,
                'code' => $code,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['userid'])) {
            return $data;
        }

        throw new \Exception('Failed to get user info: ' . json_encode($data));
    }

    /**
     * 发送文本消息
     */
    public function sendTextMessage($toUser, $content)
    {
        $accessToken = $this->getAccessToken();

        $response = $this->httpClient->post('message/send', [
            'query' => ['access_token' => $accessToken],
            'json' => [
                'touser' => $toUser,
                'msgtype' => 'text',
                'agentid' => $this->agentId,
                'text' => ['content' => $content],
                'safe' => 0,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['errcode'] === 0;
    }

    /**
     * 发送审批通知
     */
    public function sendApprovalNotification($toUser, $approvalFlow, $submission)
    {
        $accessToken = $this->getAccessToken();

        $response = $this->httpClient->post('message/send', [
            'query' => ['access_token' => $accessToken],
            'json' => [
                'touser' => $toUser,
                'msgtype' => 'textcard',
                'agentid' => $this->agentId,
                'textcard' => [
                    'title' => "新的审批申请: {$approvalFlow->name}",
                    'description' => "申请人: {$submission->submitter->name}\n申请时间: {$submission->created_at}",
                    'url' => config('app.url') . "/approvals/{$submission->id}",
                    'btntxt' => '查看详情',
                ],
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['errcode'] === 0;
    }

    /**
     * 处理通讯录变更事件
     */
    public function handleContactChange($message)
    {
        $changeType = $message['ChangeType'];

        switch ($changeType) {
            case 'create_user':
                return $this->createUser($message);
            case 'update_user':
                return $this->updateUser($message);
            case 'delete_user':
                return $this->deleteUser($message);
            default:
                return response()->json(['message' => 'success']);
        }
    }

    /**
     * 创建用户
     */
    protected function createUser($message)
    {
        $userInfo = $message['UserID'];

        User::updateOrCreate(
            ['wework_userid' => $userInfo['UserID']],
            [
                'username' => $userInfo['UserID'],
                'name' => $userInfo['Name'],
                'email' => $userInfo['Email'] ?? '',
                'phone' => $userInfo['Mobile'] ?? '',
                'department' => $userInfo['Department'] ?? '',
                'position' => $userInfo['Position'] ?? '',
                'status' => $userInfo['Status'] == 1 ? 1 : 0,
                'password' => bcrypt(uniqid()),
            ]
        );

        return response()->json(['message' => 'success']);
    }

    /**
     * 更新用户
     */
    protected function updateUser($message)
    {
        $userInfo = $message['UserID'];

        User::where('wework_userid', $userInfo['UserID'])->update([
            'name' => $userInfo['Name'],
            'email' => $userInfo['Email'] ?? '',
            'phone' => $userInfo['Mobile'] ?? '',
            'department' => $userInfo['Department'] ?? '',
            'position' => $userInfo['Position'] ?? '',
            'status' => $userInfo['Status'] == 1 ? 1 : 0,
        ]);

        return response()->json(['message' => 'success']);
    }

    /**
     * 删除用户
     */
    protected function deleteUser($message)
    {
        User::where('wework_userid', $message['UserID'])->delete();

        return response()->json(['message' => 'success']);
    }

    /**
     * 处理审批状态变更事件
     */
    public function handleApprovalChange($message)
    {
        $approvalInfo = $message['ApprovalInfo'];
        $spNo = $approvalInfo['SpNo'];

        $record = ApprovalRecord::where('sp_no', $spNo)->first();
        if ($record) {
            $record->update([
                'status' => $this->mapApprovalStatus($approvalInfo['SpStatus']),
                'comment' => $approvalInfo['Comments'] ?? '',
                'updated_at' => now(),
            ]);

            // 通知申请人审批结果
            if ($record->submission && $record->submission->submitter) {
                $this->sendApprovalResultNotification(
                    $record->submission->submitter->wework_userid,
                    $record
                );
            }
        }

        return response()->json(['message' => 'success']);
    }

    /**
     * 映射审批状态
     */
    protected function mapApprovalStatus($weworkStatus)
    {
        switch ($weworkStatus) {
            case 1: return 'approved';
            case 2: return 'rejected';
            default: return 'pending';
        }
    }

    /**
     * 同步通讯录
     */
    public function syncContacts()
    {
        $accessToken = $this->getAccessToken();

        // 获取部门列表
        $departments = $this->getDepartmentList($accessToken);

        foreach ($departments as $department) {
            // 获取部门成员
            $users = $this->getDepartmentUsers($accessToken, $department['id']);

            foreach ($users as $user) {
                User::updateOrCreate(
                    ['wework_userid' => $user['userid']],
                    [
                        'username' => $user['userid'],
                        'name' => $user['name'],
                        'email' => $user['email'] ?? '',
                        'phone' => $user['mobile'] ?? '',
                        'department' => $department['name'],
                        'position' => $user['position'] ?? '',
                        'status' => $user['status'] == 1 ? 1 : 0,
                        'password' => bcrypt(uniqid()),
                    ]
                );
            }
        }

        return count($users);
    }

    /**
     * 获取部门列表
     */
    protected function getDepartmentList($accessToken)
    {
        $response = $this->httpClient->get('department/list', [
            'query' => ['access_token' => $accessToken]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['department'])) {
            return $data['department'];
        }

        throw new \Exception('Failed to get department list: ' . json_encode($data));
    }

    /**
     * 获取部门成员
     */
    protected function getDepartmentUsers($accessToken, $departmentId)
    {
        $response = $this->httpClient->get('user/list', [
            'query' => [
                'access_token' => $accessToken,
                'department_id' => $departmentId,
                'fetch_child' => 0,
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (isset($data['userlist'])) {
            return $data['userlist'];
        }

        throw new \Exception('Failed to get department users: ' . json_encode($data));
    }
}