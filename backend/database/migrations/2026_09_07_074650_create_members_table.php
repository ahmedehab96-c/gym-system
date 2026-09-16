<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_id')->unique();
            $table->string('name');
            $table->string('avatar')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('address')->nullable();
            $table->date('dob')->nullable();
            $table->date('join_date');
            $table->foreignId('plan_id')->nullable()->constrained('membership_plans')->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('trainers')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Suspended', 'Expired'])->default('Active');
            $table->unsignedTinyInteger('attendance_rate')->default(0);
            $table->unsignedInteger('balance_due')->default(0);
            $table->string('emergency_contact')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('join_date');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
