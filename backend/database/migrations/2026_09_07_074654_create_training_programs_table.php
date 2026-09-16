<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('duration')->nullable();
            $table->enum('difficulty', ['Beginner', 'Intermediate', 'Advanced', 'All Levels'])->default('All Levels');
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->enum('status', ['Active', 'Draft', 'Archived'])->default('Draft');
            $table->timestamps();

            $table->index('status');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_programs');
    }
};
