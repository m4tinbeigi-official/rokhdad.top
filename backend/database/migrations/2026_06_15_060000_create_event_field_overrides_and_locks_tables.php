<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_field_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('field_path', 120);
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->dateTimeTz('locked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['event_id', 'field_path']);
        });

        Schema::create('event_field_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('field_path', 120)->index();
            $table->json('original_value')->nullable();
            $table->json('override_value')->nullable();
            $table->string('source_key', 80)->nullable()->index();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTimeTz('applied_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'field_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_field_overrides');
        Schema::dropIfExists('event_field_locks');
    }
};
