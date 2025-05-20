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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 50)->unique();
            $table->string('description', 200)->nullable();
            $table->string('type', 20)->comment('menu-菜单,button-按钮,api-接口');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('path', 200)->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1-启用，0-禁用');
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};