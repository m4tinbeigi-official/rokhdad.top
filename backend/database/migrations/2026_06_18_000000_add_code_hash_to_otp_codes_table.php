<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('code_hash')->nullable()->after('code');
            $table->index(['phone', 'purpose', 'used']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['phone', 'purpose', 'used']);
            $table->dropColumn('code_hash');
        });
    }
};
