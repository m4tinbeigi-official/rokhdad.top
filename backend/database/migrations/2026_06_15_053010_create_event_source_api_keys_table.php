<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_source_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_source_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_hash', 64)->index();
            $table->text('encrypted_secret');
            $table->string('status', 20)->default('active')->index();
            $table->dateTimeTz('active_from')->nullable();
            $table->dateTimeTz('expires_at')->nullable()->index();
            $table->dateTimeTz('last_used_at')->nullable();
            $table->dateTimeTz('rotated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_source_api_keys');
    }
};
