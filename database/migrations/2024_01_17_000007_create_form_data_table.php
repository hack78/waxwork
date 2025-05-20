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
        Schema::create('form_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->json('data')->comment('表单数据');
            $table->string('status', 20)->default('submitted')
                ->comment('submitted-已提交,processing-处理中,completed-已完成,rejected-已拒绝');
            $table->unsignedBigInteger('submitted_by');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->onDelete('cascade');

            $table->foreign('submitted_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // 添加索引以提高查询性能
            $table->index('status');
            $table->index('submitted_at');
            $table->index('submitted_by');
        });

        // 创建表单数据文件表，用于存储表单中的文件上传
        Schema::create('form_data_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_data_id');
            $table->unsignedBigInteger('field_id');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->timestamps();

            $table->foreign('form_data_id')
                ->references('id')
                ->on('form_data')
                ->onDelete('cascade');

            $table->foreign('field_id')
                ->references('id')
                ->on('form_fields')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_data_files');
        Schema::dropIfExists('form_data');
    }
};