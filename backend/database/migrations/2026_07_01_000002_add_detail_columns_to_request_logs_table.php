<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = Schema::getIndexes('request_logs');
        $hasStatusCodeIndex = collect($indexes)->contains(fn ($index) => $index['name'] === 'request_logs_status_code_index' || collect($index['columns'])->contains('status_code'));
        $hasCreatedAtIndex = collect($indexes)->contains(fn ($index) => $index['name'] === 'request_logs_created_at_index' || collect($index['columns'])->contains('created_at'));

        Schema::table('request_logs', function (Blueprint $table) use ($hasStatusCodeIndex, $hasCreatedAtIndex) {
            if (!Schema::hasColumn('request_logs', 'response_body')) {
                $table->text('response_body')->nullable()->after('status_code');
            }
            if (!Schema::hasColumn('request_logs', 'error_message')) {
                $table->text('error_message')->nullable()->after('response_body');
            }
            if (!Schema::hasColumn('request_logs', 'proxy_url')) {
                $table->string('proxy_url')->nullable()->after('used_proxy');
            }
            if (!Schema::hasColumn('request_logs', 'duration_ms')) {
                $table->integer('duration_ms')->nullable()->after('proxy_url')
                    ->comment('مدت زمان درخواست به میلی‌ثانیه');
            }
            // ایندکس‌ها
            if (!$hasStatusCodeIndex) {
                $table->index('status_code');
            }
            if (!$hasCreatedAtIndex) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        $indexes = Schema::getIndexes('request_logs');
        $hasStatusCodeIndex = collect($indexes)->contains(fn ($index) => $index['name'] === 'request_logs_status_code_index' || collect($index['columns'])->contains('status_code'));
        $hasCreatedAtIndex = collect($indexes)->contains(fn ($index) => $index['name'] === 'request_logs_created_at_index' || collect($index['columns'])->contains('created_at'));

        Schema::table('request_logs', function (Blueprint $table) use ($hasStatusCodeIndex, $hasCreatedAtIndex) {
            if ($hasStatusCodeIndex) {
                $table->dropIndex(['status_code']);
            }
            if ($hasCreatedAtIndex) {
                $table->dropIndex(['created_at']);
            }
            $table->dropColumn(['response_body', 'error_message', 'proxy_url', 'duration_ms']);
        });
    }
};
