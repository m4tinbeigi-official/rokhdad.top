<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Actor: the user who performed the action (nullable for system events).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_label')->nullable()->comment('نام/ایمیل کاربر در زمان ثبت رویداد');

            // Action: stable event key, e.g. auth.login, payout.completed.
            $table->string('action')->index();

            // Subject: the affected record, e.g. Payout #12.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->text('description')->nullable();
            $table->json('properties')->nullable()->comment('داده‌های اضافی رویداد');

            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
