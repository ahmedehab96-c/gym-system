<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payment\Exceptions\PaymentGatewayException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPayments;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use InteractsWithPayments, RefreshDatabase;

    public function test_a_billing_admin_can_start_a_checkout_session(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan(monthlyPrice: 79);
        $admin = $this->billingAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Monthly',
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['checkoutUrl']]);
        $this->assertCount(1, $gateway->calls);
        $this->assertSame(7900, $gateway->calls[0]['args']['unit_amount']);
        $this->assertSame('month', $gateway->calls[0]['args']['interval']);

        // A checkout session existing must NOT activate anything by itself.
        $this->assertDatabaseHas('payment_transactions', [
            'tenant_id' => $admin->tenant_id,
            'status' => 'Pending',
            'amount' => 79,
        ]);
        $this->assertDatabaseMissing('tenant_subscriptions', ['tenant_id' => $admin->tenant_id, 'status' => 'Active']);
    }

    public function test_yearly_billing_cycle_uses_the_yearly_price_and_interval(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan(monthlyPrice: 79, yearlyPrice: 790);
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Yearly',
        ])->assertCreated();

        $this->assertSame(79000, $gateway->calls[0]['args']['unit_amount']);
        $this->assertSame('year', $gateway->calls[0]['args']['interval']);
        $this->assertDatabaseHas('payment_transactions', ['tenant_id' => $admin->tenant_id, 'amount' => 790]);
    }

    public function test_checkout_requires_a_valid_plan(): void
    {
        $this->bindFakePaymentGateway();
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => 999999,
            'billing_cycle' => 'Monthly',
        ])->assertStatus(422);
    }

    public function test_a_non_admin_staff_member_cannot_start_checkout(): void
    {
        $this->bindFakePaymentGateway();
        $plan = $this->paidPlan();
        $tenant = Tenant::default();
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Monthly',
        ])->assertStatus(403);
    }

    public function test_a_guest_cannot_start_checkout(): void
    {
        $this->bindFakePaymentGateway();
        $plan = $this->paidPlan();

        $this->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Monthly',
        ])->assertStatus(401);
    }

    public function test_gateway_failure_returns_a_clean_502_and_creates_no_transaction(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $gateway->throwOnCreateCheckout = new PaymentGatewayException('Could not reach the payment gateway.');
        $plan = $this->paidPlan();
        $admin = $this->billingAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id,
            'billing_cycle' => 'Monthly',
        ]);

        $response->assertStatus(502);
        $this->assertSame(0, PaymentTransaction::count());
    }
}
