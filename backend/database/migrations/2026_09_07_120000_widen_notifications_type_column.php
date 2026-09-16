<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widens notifications.type from a fixed enum to a plain string. Phase 10
 * introduces several more event types (Membership Expired, Payment
 * Pending/Failed, Class Cancellation, Maintenance Overdue, ...) and new
 * types will keep being added as delivery channels grow, so the valid set
 * is enforced in NotificationType (app layer) instead of a DB enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->default('System Notification')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'Membership Expiring',
                'Payment Received',
                'New Member',
                'Class Reminder',
                'Maintenance Due',
                'System Notification',
            ])->change();
        });
    }
};
