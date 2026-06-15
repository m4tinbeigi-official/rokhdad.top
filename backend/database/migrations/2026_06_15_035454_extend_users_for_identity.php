<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_e164', 20)->nullable()->unique()->after('email_verified_at');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_e164');
            $table->string('status', 32)->default('active')->index()->after('password');
            $table->string('locale', 12)->default('fa')->after('status');
            $table->string('timezone', 64)->default('Asia/Tehran')->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('timezone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_e164']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'phone_e164',
                'phone_verified_at',
                'status',
                'locale',
                'timezone',
                'last_login_at',
            ]);
        });
    }
};
