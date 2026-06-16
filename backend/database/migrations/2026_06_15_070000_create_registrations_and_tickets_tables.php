<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Internal event registrations
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, attended
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, refunded, free
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('total_amount')->default(0); // in Rials
            $table->string('currency', 3)->default('IRR');
            $table->json('form_data')->nullable(); // custom form answers
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['event_id', 'status']);
        });

        // Tickets per registration
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_number', 64)->unique();
            $table->string('qr_code_token', 128)->unique();
            $table->string('status')->default('issued'); // issued, used, cancelled, expired
            $table->unsignedBigInteger('price')->default(0); // in Rials
            $table->string('seat_info')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index('qr_code_token');
        });

        // Event ticket types (capacity tiers)
        Schema::create('event_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name'); // e.g. "VIP", "General"
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0); // 0 = free
            $table->string('currency', 3)->default('IRR');
            $table->unsignedInteger('capacity')->nullable(); // null = unlimited
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('max_per_user')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'is_active']);
        });

        // Add internal event fields to events table
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('is_featured');
            $table->boolean('registration_open')->default(false)->after('is_internal');
            $table->unsignedInteger('capacity')->nullable()->after('registration_open');
            $table->timestamp('registration_starts_at')->nullable()->after('capacity');
            $table->timestamp('registration_ends_at')->nullable()->after('registration_starts_at');
            $table->boolean('requires_approval')->default(false)->after('registration_ends_at');
            $table->text('registration_instructions')->nullable()->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'is_internal', 'registration_open', 'capacity',
                'registration_starts_at', 'registration_ends_at',
                'requires_approval', 'registration_instructions',
            ]);
        });
        Schema::dropIfExists('event_ticket_types');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('registrations');
    }
};
