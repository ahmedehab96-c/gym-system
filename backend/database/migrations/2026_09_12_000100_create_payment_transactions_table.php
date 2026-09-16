<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per real money movement against a tenant's SaaS subscription —
 * a checkout charge or a refund (Phase 23). Distinct from
 * subscription_invoices: an invoice is a billing-period record ("you owe
 * $79 for Sep 10 - Oct 10"), a transaction is the gateway-side proof of
 * an actual attempt to collect (or return) that money. `reference` is
 * globally unique because gateway ids (Stripe session/charge/refund ids)
 * already are — this is also the primary duplicate-processing guard: a
 * webhook or a verify-on-return request for the same reference can only
 * ever finalize once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 30);
            $table->enum('type', ['Charge', 'Refund'])->default('Charge');
            $table->string('reference')->unique();
            // For a Refund row, the reference of the Charge it refunds.
            $table->string('related_reference')->nullable();
            $table->string('gateway_payment_intent_id')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 10)->default('USD');
            $table->enum('status', ['Pending', 'Paid', 'Failed', 'Refunded'])->default('Pending');
            $table->timestamp('paid_at')->nullable();
            // Safe metadata only (plan id/name, billing cycle) — never card
            // data or gateway secrets, see App\Services\Payment.
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('gateway_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
