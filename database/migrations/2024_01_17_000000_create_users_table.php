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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('name', 50);
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->unique();
            $table->string('department', 50)->nullable();
            $table->string('position', 50)->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1-启用，0-禁用');
            $table->string('wework_userid', 100)->nullable()->unique()->comment('企业微信用户ID');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};