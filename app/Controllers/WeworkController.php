<?php

namespace App\Controllers;

use App\Services\WeworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeworkController extends Controller
{
    /**
     * 处理企业微信回调
     */
    public function handleCallback(Request $request)
    {
        $service = app(WeworkService::class);
        
        try {
            // 验证回调URL
            if ($request->has('echostr')) {
                return $service->verifyURL(
                    $request->input('msg_signature'),
                    $request->input('timestamp'),
                    $request->input('nonce'),
                    $request->input('echostr')
                );
            }

            // 解密消息
            $message = $service->decryptMessage(
                $request->input('msg_signature'),
                $request->input('timestamp'),
                $request->input('nonce'),
                $request->getContent()
            );

            Log::info('Received Wework callback:', $message);

            // 处理不同类型的消息
            switch ($message['MsgType']) {
                case 'event':
                    return $this->handleEvent($message);
                case 'text':
                    return $this->handleTextMessage($message);
                // 其他消息类型...
                default:
                    return response()->json(['message' => 'success']);
            }
        } catch (\Exception $e) {
            Log::error('Wework callback error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'error'], 500);
        }
    }

    /**
     * 处理事件消息
     */
    protected function handleEvent($message)
    {
        $service = app(WeworkService::class);

        switch ($message['Event']) {
            case 'change_contact':
                // 处理通讯录变更事件
                return $service->handleContactChange($message);
            case 'sys_approval_change':
                // 处理审批状态变更事件
                return $service->handleApprovalChange($message);
            case 'click':
                // 处理菜单点击事件
                return $service->handleMenuClick($message);
            default:
                return response()->json(['message' => 'success']);
        }
    }

    /**
     * 处理文本消息
     */
    protected function handleTextMessage($message)
    {
        $service = app(WeworkService::class);
        
        // 简单回复
        if (str_contains($message['Content'], '帮助')) {
            $service->sendTextMessage(
                $message['FromUserName'],
                config('wework.agent_id'),
                "您好，我是企业微信助手。\n回复'帮助'获取帮助信息"
            );
        }

        return response()->json(['message' => 'success']);
    }

    /**
     * 获取企业微信用户信息
     */
    public function getUserInfo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $service = app(WeworkService::class);
        $userInfo = $service->getUserInfoByCode($request->code);

        return response()->json($userInfo);
    }

    /**
     * 发送企业微信消息
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'content' => 'required|string',
        ]);

        $service = app(WeworkService::class);
        $result = $service->sendTextMessage(
            $request->user_id,
            config('wework.agent_id'),
            $request->content
        );

        return response()->json(['success' => $result]);
    }
}