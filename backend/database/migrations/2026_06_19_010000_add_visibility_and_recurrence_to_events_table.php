<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('status')->index();
            $table->string('series_slug')->nullable()->after('visibility')->index();
            $table->string('recurrence_rule')->nullable()->after('series_slug');
            $table->timestamp('recurrence_ends_at')->nullable()->after('recurrence_rule');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'series_slug', 'recurrence_rule', 'recurrence_ends_at']);
        });
    }
};
