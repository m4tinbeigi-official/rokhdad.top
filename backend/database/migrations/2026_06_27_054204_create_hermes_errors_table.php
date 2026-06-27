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
        Schema::create('hermes_errors', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g. exception, validation, hermes
            $table->text('message');
            $table->longText('trace');
            $table->json('payload')->nullable(); // optional extra data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hermes_errors');
    }
};
