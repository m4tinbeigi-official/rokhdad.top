<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_source_id')
                ->constrained('event_sources')
                ->onDelete('cascade');
            $table->string('url');
            $table->string('method', 10)->default('GET');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('used_proxy')->default(false);
            $table->string('proxy_url')->nullable();
            $table->integer('duration_ms')->nullable()->comment('مدت زمان درخواست به میلی‌ثانیه');
            $table->timestamps();

            // ایندکس برای جستجوی سریع‌تر
            $table->index('event_source_id');
            $table->index('status_code');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
