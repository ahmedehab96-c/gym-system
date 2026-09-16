<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->string('specialty');
            $table->json('specialties')->nullable();
            $table->string('experience')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->text('bio')->nullable();
            $table->enum('status', ['Active', 'On Leave', 'Inactive'])->default('Active');
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('sessions_completed')->default(0);
            $table->json('schedule')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
