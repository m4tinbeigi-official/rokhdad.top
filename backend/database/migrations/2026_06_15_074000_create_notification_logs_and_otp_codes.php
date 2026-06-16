<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel'); // sms, email
            $table->string('provider'); // sms.ir, pakett
            $table->string('recipient'); // phone or email
            $table->string('type'); // otp, registration_confirm, reminder, etc.
            $table->text('message')->nullable();
            $table->string('status')->default('queued'); // queued, sent, failed
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['channel', 'status']);
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code', 10);
            $table->string('purpose')->default('login'); // login, verify
            $table->boolean('used')->default(false);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('notification_logs');
    }
};
