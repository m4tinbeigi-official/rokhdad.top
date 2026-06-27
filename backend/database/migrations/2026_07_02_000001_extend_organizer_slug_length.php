<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Increase slug length to accommodate long UTF-8 slugs from Evand source
        Schema::table('organizers', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('slug', 512)->change();
        });
    }

    public function down(): void
    {
        // Revert back to original length (255)
        Schema::table('organizers', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('slug', 255)->change();
        });
    }
};
