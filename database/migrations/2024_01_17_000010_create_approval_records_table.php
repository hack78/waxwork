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
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->unsignedBigInteger('node_id');
            $table->unsignedBigInteger('form_data_id');
            $table->unsignedBigInteger('approver_id');
            $table->string('status', 20)->comment('pending-待审批,approved-已通过,rejected-已拒绝');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->string('sp_no')->nullable()->comment('企业微信审批单号');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamps();

            $table->foreign('flow_id')
                ->references('id')
                ->on('approval_flows')
                ->onDelete('cascade');

            $table->foreign('node_id')
                ->references('id')
                ->on('approval_nodes')
                ->onDelete('cascade');

            $table->foreign('form_data_id')
                ->references('id')
                ->on('form_data')
                ->onDelete('cascade');

            $table->foreign('approver_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // 添加索引以提高查询性能
            $table->index('status');
            $table->index('sp_no');
            $table->index('approved_at');
        });

        // 创建审批附件表
        Schema::create('approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->timestamps();

            $table->foreign('record_id')
                ->references('id')
                ->on('approval_records')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_attachments');
        Schema::dropIfExists('approval_records');
    }
};