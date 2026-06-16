<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('favorite_category_ids')->nullable();
            $table->json('favorite_city_ids')->nullable();
            $table->string('preferred_event_type')->nullable(); // online, in_person
            $table->string('notification_channel')->default('sms'); // sms, email, both
            $table->boolean('notify_new_events')->default(true);
            $table->boolean('notify_reminders')->default(true);
            $table->timestamps();
        });

        Schema::create('saved_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_events');
        Schema::dropIfExists('user_preferences');
    }
};
