<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// 企业微信回调路由
Route::post('/api/wework/callback', [\App\Http\Controllers\WeworkController::class, 'handleCallback']);

// 认证路由
Route::prefix('auth')->group(function () {
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('user', [\App\Http\Controllers\AuthController::class, 'user'])->middleware('auth:sanctum');
});

// API路由
Route::middleware('auth:sanctum')->prefix('api')->group(function () {
    // 用户管理
    Route::apiResource('users', \App\Http\Controllers\UserController::class);
    
    // 表单管理
    Route::apiResource('forms', \App\Http\Controllers\FormController::class);
    Route::get('forms/{form}/fields', [\App\Http\Controllers\FormController::class, 'fields']);
    
    // 审批流程
    Route::apiResource('approval-flows', \App\Http\Controllers\ApprovalFlowController::class);
    Route::post('approval-flows/{approval_flow}/submit', [\App\Http\Controllers\ApprovalFlowController::class, 'submit']);
});