<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('source_key')->nullable()->after('id')->index();
            $table->string('external_id')->nullable()->after('source_key')->index();
            $table->string('logo_url')->nullable()->after('phone_e164');
            $table->string('cover_url')->nullable()->after('logo_url');
            $table->json('metadata')->nullable()->after('social_links');
            $table->unique(['source_key', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropUnique(['source_key', 'external_id']);
            $table->dropColumn(['source_key', 'external_id', 'logo_url', 'cover_url', 'metadata']);
        });
    }
};
