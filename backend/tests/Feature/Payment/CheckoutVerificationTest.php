<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPayments;
use Tests\TestCase;

class CheckoutVerificationTest extends TestCase
{
    use InteractsWithPayments, RefreshDatabase;

    public function test_verifying_a_paid_session_activates_the_subscription_server_side(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan(monthlyPrice: 79);
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
        ]);
        $transaction = PaymentTransaction::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $gateway->stageCheckoutSession($transaction->reference, new CheckoutSessionStatus(
            id: $transaction->reference,
            status: 'complete',
            paymentStatus: 'paid',
            subscriptionId: 'sub_test_123',
            customerId: 'cus_test_123',
            paymentIntentId: 'pi_test_123',
            currentPeriodEnd: Carbon::today()->addMonth(),
        ));

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/checkout/verify?session_id='.$transaction->reference);

        $response->assertOk()
            ->assertJsonPath('data.transaction.status', 'Paid')
            ->assertJsonPath('data.subscription.status', 'Active')
            ->assertJsonPath('data.subscription.price', 79);

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $admin->tenant_id, 'status' => 'Active', 'gateway_subscription_id' => 'sub_test_123',
        ]);
        $this->assertDatabaseHas('subscription_invoices', ['tenant_id' => $admin->tenant_id, 'status' => 'Paid']);
        $this->assertDatabaseHas('payment_transactions', [
            'reference' => $transaction->reference, 'status' => 'Paid', 'gateway_payment_intent_id' => 'pi_test_123',
        ]);
    }

    public function test_verifying_an_unpaid_session_does_not_activate_anything(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan();
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
        ]);
        $transaction = PaymentTransaction::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $gateway->stageCheckoutSession($transaction->reference, new CheckoutSessionStatus(
            id: $transaction->reference, status: 'open', paymentStatus: 'unpaid',
            subscriptionId: null, customerId: null, paymentIntentId: null, currentPeriodEnd: null,
        ));

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/checkout/verify?session_id='.$transaction->reference);

        $response->assertOk()->assertJsonPath('data.transaction.status', 'Pending');
        $this->assertDatabaseMissing('tenant_subscriptions', ['tenant_id' => $admin->tenant_id, 'status' => 'Active']);
    }

    public function test_verification_is_idempotent(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan();
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
        ]);
        $transaction = PaymentTransaction::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $gateway->stageCheckoutSession($transaction->reference, new CheckoutSessionStatus(
            id: $transaction->reference, status: 'complete', paymentStatus: 'paid',
            subscriptionId: 'sub_test_999', customerId: 'cus_test_999', paymentIntentId: 'pi_test_999',
            currentPeriodEnd: Carbon::today()->addMonth(),
        ));

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/checkout/verify?session_id='.$transaction->reference)->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/subscription/checkout/verify?session_id='.$transaction->reference)->assertOk();

        $this->assertSame(1, SubscriptionInvoice::where('tenant_id', $admin->tenant_id)->where('status', 'Paid')->count());
        $this->assertSame(1, PaymentTransaction::where('reference', $transaction->reference)->count());
    }

    public function test_a_tenant_cannot_verify_another_tenants_checkout_session(): void
    {
        $this->bindFakePaymentGateway();
        $plan = $this->paidPlan();
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $adminA = $this->billingAdmin($tenantA);
        $adminB = $this->billingAdmin($tenantB);

        $this->actingAs($adminA, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
        ]);
        $transaction = PaymentTransaction::where('tenant_id', $tenantA->id)->firstOrFail();

        $this->actingAs($adminB, 'sanctum')
            ->getJson('/api/v1/subscription/checkout/verify?session_id='.$transaction->reference)
            ->assertStatus(404);
    }

    public function test_verifying_an_unknown_session_returns_not_found(): void
    {
        $this->bindFakePaymentGateway();
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/subscription/checkout/verify?session_id=cs_test_does_not_exist')
            ->assertStatus(404);
    }
}
