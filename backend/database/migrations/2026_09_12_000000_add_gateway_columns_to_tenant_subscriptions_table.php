<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a tenant's subscription to the real payment gateway's own
 * recurring-billing objects (Phase 23). Nullable because a subscription
 * can still exist without ever having gone through a paid checkout (a
 * Trial, or one started via the pre-existing instant-activate
 * SubscriptionService::start() path — see its docblock).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->string('gateway', 30)->nullable()->after('expires_at');
            $table->string('gateway_customer_id')->nullable()->after('gateway');
            $table->string('gateway_subscription_id')->nullable()->after('gateway_customer_id');

            $table->index('gateway_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['gateway_subscription_id']);
            $table->dropColumn(['gateway', 'gateway_customer_id', 'gateway_subscription_id']);
        });
    }
};
