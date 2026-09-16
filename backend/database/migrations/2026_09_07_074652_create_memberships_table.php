<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('membership_plans')->restrictOnDelete();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->unsignedInteger('price');
            $table->enum('status', ['Active', 'Expiring Soon', 'Expired', 'Suspended'])->default('Active');
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
