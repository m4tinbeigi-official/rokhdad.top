<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_source_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('source_key', 80)->index();
            $table->string('external_id');
            $table->string('external_url')->nullable();
            $table->string('payload_hash', 128)->nullable()->index();
            $table->string('snapshot_ref')->nullable();
            $table->dateTimeTz('first_seen_at')->nullable();
            $table->dateTimeTz('last_seen_at')->nullable()->index();
            $table->dateTimeTz('last_synced_at')->nullable();
            $table->string('sync_status', 20)->default('pending')->index();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['source_key', 'external_id']);
            $table->index(['event_id', 'source_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_source_attributions');
    }
};
