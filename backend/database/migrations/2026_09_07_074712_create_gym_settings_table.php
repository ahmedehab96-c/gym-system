<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('working_days')->nullable();
            $table->string('open_time', 5)->nullable();
            $table->string('close_time', 5)->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->string('currency', 10)->default('EGP');
            $table->string('timezone')->default('Africa/Cairo');
            $table->string('language')->default('English');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_settings');
    }
};
