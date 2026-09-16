<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->foreignId('trainer_id')->constrained('trainers')->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->unsignedInteger('capacity')->default(0);
            $table->enum('status', ['Scheduled', 'Full', 'Cancelled', 'Completed'])->default('Scheduled');
            $table->string('color', 20)->nullable();
            $table->timestamps();

            $table->index(['day', 'start_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_classes');
    }
};
