<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('gym_classes')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->timestamp('booked_at')->useCurrent();

            $table->unique(['class_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
