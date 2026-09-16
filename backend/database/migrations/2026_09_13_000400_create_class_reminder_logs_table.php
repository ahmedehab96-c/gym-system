<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup guard for App\Console\Commands\SendClassReminders — a class can
 * legitimately recur (same `gym_classes` row, reused weekly), so a
 * single "reminded" flag on the class itself can't work the way the
 * exact-day-match trick does for once-only lifecycle events (see
 * UpdateSubscriptionStatuses). One row per (class, calendar date) it
 * was reminded for is unambiguous regardless of recurrence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_id')->constrained()->cascadeOnDelete();
            $table->date('for_date');
            $table->timestamps();

            $table->unique(['gym_class_id', 'for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_reminder_logs');
    }
};
