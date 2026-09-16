<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each tenant's current subscription state — one row per tenant (unique
 * tenant_id), updated in place as it moves through its lifecycle
 * (Trial -> Active -> Past Due -> Expired/Cancelled), the same way an
 * existing Membership row is updated in place by MembershipService
 * rather than superseded by a new row on every transition. Historical
 * billing events live in subscription_invoices, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->enum('status', ['Trial', 'Active', 'Past Due', 'Cancelled', 'Expired'])->default('Trial');
            $table->enum('billing_cycle', ['Monthly', 'Yearly'])->default('Monthly');

            // Snapshot of the plan's price at subscription time — plan
            // prices can change later without silently changing what an
            // already-subscribed tenant is being charged.
            $table->unsignedInteger('price')->default(0);

            $table->date('trial_starts_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->date('started_at')->nullable();
            $table->date('next_billing_at')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
