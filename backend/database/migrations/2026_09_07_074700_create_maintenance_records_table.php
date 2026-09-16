<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('type');
            $table->string('technician')->nullable();
            $table->date('date');
            $table->unsignedInteger('cost')->default(0);
            $table->enum('status', ['Upcoming', 'Overdue', 'Completed', 'In Progress'])->default('Upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
