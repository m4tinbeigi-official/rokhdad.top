<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('channel'); // email, sms, both
            $table->json('target_audience')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, active, paused, cancelled
            $table->string('template')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['organizer_id', 'status']);
        });

        Schema::create('campaign_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('type'); // sms, email
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('send_at')->nullable();
            $table->string('status')->default('scheduled'); // scheduled, sent, failed
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
        });

        Schema::create('campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('metric_type'); // sent, opened, clicked, converted
            $table->unsignedInteger('value');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'metric_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_analytics');
        Schema::dropIfExists('campaign_messages');
        Schema::dropIfExists('campaigns');
    }
};
