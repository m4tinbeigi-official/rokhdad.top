<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('event_sources', 'proxy_url')) {
                $table->string('proxy_url')->nullable()->after('api_base_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('event_sources', function (Blueprint $table) {
            $table->dropColumn('proxy_url');
        });
    }
};
