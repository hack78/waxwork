<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('配置名称');
            $table->string('key', 50)->unique()->comment('配置键');
            $table->text('value')->comment('配置值');
            $table->string('type', 20)->default('string')->comment('值类型：string,number,boolean,json');
            $table->string('description', 200)->nullable()->comment('配置描述');
            $table->timestamps();

            // 添加索引以提高查询性能
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};