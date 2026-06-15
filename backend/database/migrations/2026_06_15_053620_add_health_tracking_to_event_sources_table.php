<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sources', function (Blueprint $table) {
            $table->string('health_status', 20)->default('unknown')->index()->after('status');
            $table->unsignedInteger('consecutive_failures')->default(0)->after('health_status');
            $table->dateTimeTz('last_success_at')->nullable()->after('last_checked_at');
            $table->dateTimeTz('last_failure_at')->nullable()->after('last_success_at');
            $table->text('last_error_message')->nullable()->after('last_failure_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_sources', function (Blueprint $table) {
            $table->dropColumn([
                'health_status',
                'consecutive_failures',
                'last_success_at',
                'last_failure_at',
                'last_error_message',
            ]);
        });
    }
};
