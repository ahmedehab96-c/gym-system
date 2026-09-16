<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->unsignedInteger('price');
            $table->string('duration_label');
            $table->unsignedInteger('duration_days');
            $table->json('features')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('color', 20)->nullable();
            $table->boolean('popular')->default(false);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
