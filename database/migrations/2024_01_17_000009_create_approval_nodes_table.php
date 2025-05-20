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
        Schema::create('approval_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->string('name', 50);
            $table->string('type', 20)->comment('approval-审批,notify-通知');
            $table->string('approver_type', 20)->comment('user-用户,role-角色,department-部门');
            $table->unsignedBigInteger('approver_id');
            $table->json('conditions')->nullable()->comment('条件配置');
            $table->integer('order')->default(0)->comment('节点顺序');
            $table->tinyInteger('status')->default(1)->comment('1-启用，0-禁用');
            $table->timestamps();

            $table->foreign('flow_id')
                ->references('id')
                ->on('approval_flows')
                ->onDelete('cascade');

            // 添加索引以提高查询性能
            $table->index(['flow_id', 'order']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_nodes');
    }
};