<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->date('date');
            $table->string('check_in', 5);
            $table->string('check_out', 5)->nullable();
            $table->string('duration', 20)->nullable();
            $table->enum('method', ['QR Code', 'Manual', 'Card'])->default('Manual');
            $table->timestamps();

            $table->index(['member_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
