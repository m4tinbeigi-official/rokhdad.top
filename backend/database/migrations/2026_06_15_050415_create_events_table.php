<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organizer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->dateTimeTz('starts_at')->nullable()->index();
            $table->dateTimeTz('ends_at')->nullable()->index();
            $table->string('timezone')->default('Asia/Tehran');
            $table->string('event_type', 20)->default('in_person')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('online_url')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['city_id', 'starts_at']);
            $table->index(['category_id', 'starts_at']);
        });

        Schema::create('event_person', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role_title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['event_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_person');
        Schema::dropIfExists('events');
    }
};
