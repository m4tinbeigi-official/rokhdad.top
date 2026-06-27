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
        Schema::create('event_merge_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('duplicate_event_id')->constrained('events')->cascadeOnDelete();
            $table->decimal('confidence_score', 5, 2);
            $table->text('ai_reasoning')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            
            // Ensure we don't have multiple pending proposals for the same pair
            $table->unique(['primary_event_id', 'duplicate_event_id', 'status'], 'emp_unique_active_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_merge_proposals');
    }
};
