<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->text('body');
            $table->string('status')->default('pending'); // pending, approved, rejected, spam
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['user_id']);
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 1–5
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comments');
    }
};
