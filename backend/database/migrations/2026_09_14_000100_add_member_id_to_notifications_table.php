<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reuses the existing `notifications` table (App\Models\AppNotification)
 * for member-facing in-app notifications too, rather than creating a
 * parallel table — a row belongs to exactly one of user_id/member_id,
 * never both (see AppNotification and NotificationService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->index(['member_id', 'read']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
