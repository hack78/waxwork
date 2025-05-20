<?php

namespace App\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['用户名或密码错误'],
            ]);
        }

        // 创建访问令牌
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration'),
            'user' => $user,
        ]);
    }

    /**
     * 用户登出
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '成功登出']);
    }

    /**
     * 获取当前用户信息
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * 企业微信扫码登录
     */
    public function weworkLogin(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $service = app(\App\Services\WeworkService::class);
        $userInfo = $service->getUserInfoByCode($request->code);

        // 查找或创建用户
        $user = User::firstOrCreate(
            ['wework_userid' => $userInfo['userid']],
            [
                'username' => $userInfo['userid'],
                'name' => $userInfo['name'],
                'email' => $userInfo['email'] ?? '',
                'phone' => $userInfo['mobile'] ?? '',
                'password' => Hash::make(uniqid()),
            ]
        );

        // 创建访问令牌
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration'),
            'user' => $user,
        ]);
    }
}