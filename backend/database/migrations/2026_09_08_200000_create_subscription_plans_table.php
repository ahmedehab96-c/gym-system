<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The platform's own pricing catalog (Free/Basic/Pro/Enterprise) — not to
 * be confused with membership_plans, which are a single gym's pricing
 * tiers for ITS members. This table is platform-global (no tenant_id):
 * every tenant chooses from the same shared catalog.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('monthly_price');
            $table->unsignedInteger('yearly_price');
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->json('features')->nullable();

            // Configurable rather than hardcoded — a key missing or set to
            // null means "unlimited" for that limit; see
            // App\Services\SubscriptionLimitService, the single place
            // that interprets this column.
            $table->json('limits');

            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
