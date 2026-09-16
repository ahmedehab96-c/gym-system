<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A delivery-attempt log for every external-channel send (email,
 * WhatsApp, push) — distinct from the `notifications` table (in-app
 * inbox items). This is what backs "notification history" / "failed
 * notifications" in the Settings UI (Phase 24 §8) and gives the queued
 * jobs (App\Jobs\Send*NotificationJob) somewhere to record success or
 * failure with a safe (never secret-containing) error message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->enum('channel', ['email', 'whatsapp', 'push']);
            $table->string('recipient');
            $table->enum('status', ['Pending', 'Sent', 'Failed'])->default('Pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
