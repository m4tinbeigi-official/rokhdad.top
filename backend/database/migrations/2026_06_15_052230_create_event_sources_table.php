<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 80)->unique();
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->string('api_base_url')->nullable();
            $table->string('proxy_url')->nullable();
            $table->string('auth_type', 20)->default('none');
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->json('config')->nullable();
            $table->dateTimeTz('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sources');
    }
};
