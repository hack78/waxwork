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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('name', 50);
            $table->string('label', 50);
            $table->string('type', 20)->comment('text,textarea,radio,checkbox,select,date,file等');
            $table->tinyInteger('required')->default(0)->comment('1-必填，0-非必填');
            $table->string('placeholder', 100)->nullable();
            $table->string('default_value', 500)->nullable();
            $table->json('options')->nullable()->comment('选项配置，用于radio,checkbox,select类型');
            $table->json('validation_rules')->nullable()->comment('验证规则');
            $table->integer('order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('1-启用，0-禁用');
            $table->timestamps();

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};