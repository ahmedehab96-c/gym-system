<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->enum('category', ['Cardio', 'Strength', 'Free Weights', 'Functional']);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('condition', ['Excellent', 'Good', 'Needs Maintenance', 'Out of Service'])->default('Excellent');
            $table->string('location')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable();
            $table->enum('status', ['In Use', 'Under Maintenance', 'Retired'])->default('In Use');
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
