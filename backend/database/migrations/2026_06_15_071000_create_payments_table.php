<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('gateway'); // zarinpal, zibal
            $table->string('gateway_authority')->nullable(); // gateway transaction ref
            $table->string('gateway_ref_id')->nullable(); // verified ref
            $table->string('status')->default('pending'); // pending, paid, failed, refunded
            $table->unsignedBigInteger('amount'); // in Rials
            $table->string('currency', 3)->default('IRR');
            $table->string('callback_url')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('gateway_authority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
