<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Billing history for a tenant's SaaS subscription — entirely separate
 * from the existing `invoices` table, which bills a gym's own members.
 * No real payment gateway is wired in yet (see SubscriptionService); this
 * table exists so the billing-history API and lifecycle jobs have
 * somewhere real to read from and write to once one is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->unsignedInteger('amount');
            $table->string('currency', 10)->default('USD');
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->enum('status', ['Pending', 'Paid', 'Failed', 'Refunded', 'Cancelled'])->default('Pending');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
