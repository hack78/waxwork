<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 企业微信同步命令
Artisan::command('wework:sync-contacts', function () {
    $this->info('开始同步企业微信通讯录...');
    $service = app(\App\Services\WeworkService::class);
    $service->syncContacts();
    $this->info('同步完成!');
})->purpose('同步企业微信通讯录数据');

// 清理过期数据命令
Artisan::command('wework:clean-data', function () {
    $this->info('开始清理过期数据...');
    $service = app(\App\Services\DataCleanupService::class);
    $count = $service->cleanupExpiredData();
    $this->info("清理完成! 共清理 {$count} 条数据");
})->purpose('清理系统中的过期数据');