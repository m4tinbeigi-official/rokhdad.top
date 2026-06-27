<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_settings', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->nullable();
            $table->integer('expires_in')->nullable();
            $table->integer('created_at_timestamp')->nullable();
            $table->string('analytics_property_id')->nullable();
            $table->string('search_console_site_url')->nullable();
            $table->timestamps();
        });

        Schema::create('google_analytics_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('sessions')->default(0);
            $table->integer('pageviews')->default(0);
            $table->integer('active_users')->default(0);
            $table->float('bounce_rate')->default(0.0);
            $table->float('avg_session_duration')->default(0.0);
            $table->timestamps();
        });

        Schema::create('google_search_console_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->float('ctr')->default(0.0);
            $table->float('position')->default(0.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_search_console_metrics');
        Schema::dropIfExists('google_analytics_metrics');
        Schema::dropIfExists('google_settings');
    }
};
