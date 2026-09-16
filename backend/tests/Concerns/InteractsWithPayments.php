<?php

namespace Tests\Concerns;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\Contracts\PaymentGatewayContract;
use Tests\Fakes\FakePaymentGateway;

trait InteractsWithPayments
{
    /** Swaps the real Stripe provider for a fake one — no test ever makes a real network call. */
    protected function bindFakePaymentGateway(): FakePaymentGateway
    {
        $fake = new FakePaymentGateway;
        $this->app->instance(PaymentGatewayContract::class, $fake);

        return $fake;
    }

    /** A tenant admin allowed to manage billing (Super Admin/Admin — see the 'subscription' route group's role gate). */
    protected function billingAdmin(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::default();

        return User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);
    }

    protected function paidPlan(int $monthlyPrice = 79, int $yearlyPrice = 790): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create([
            'monthly_price' => $monthlyPrice,
            'yearly_price' => $yearlyPrice,
            'status' => 'Active',
        ]);
    }
}
