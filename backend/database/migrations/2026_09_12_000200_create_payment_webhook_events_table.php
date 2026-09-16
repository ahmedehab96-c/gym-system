<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The idempotency ledger for incoming payment-gateway webhooks (Phase
 * 23) — not tenant-scoped (no BelongsToTenant): a webhook event is a
 * platform/system-level occurrence, arriving before we necessarily know
 * which tenant it belongs to. The unique (provider, event_id) pair is
 * the actual duplicate-delivery guard; `payload` stores only a small,
 * curated, non-sensitive summary (see PaymentWebhookController), never
 * the full raw gateway payload or any secret.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id');
            $table->string('type');
            $table->enum('status', ['Processed', 'Ignored', 'Failed'])->default('Processed');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
