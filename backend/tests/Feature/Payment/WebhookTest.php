<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\Payment\DTO\CheckoutSessionStatus;
use App\Services\Payment\DTO\GatewaySubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPayments;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use InteractsWithPayments, RefreshDatabase;

    private function webhookPayload(string $type, array $object, ?string $eventId = null): array
    {
        return [
            'id' => $eventId ?? 'evt_test_'.Str::random(16),
            'type' => $type,
            'data' => ['object' => $object],
        ];
    }

    public function test_checkout_session_completed_activates_the_subscription(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $plan = $this->paidPlan(monthlyPrice: 79);
        $admin = $this->billingAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/subscription/checkout', [
            'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
        ]);
        $transaction = PaymentTransaction::where('tenant_id', $admin->tenant_id)->firstOrFail();

        $gateway->stageCheckoutSession($transaction->reference, new CheckoutSessionStatus(
            id: $transaction->reference, status: 'complete', paymentStatus: 'paid',
            subscriptionId: 'sub_webhook_1', customerId: 'cus_webhook_1', paymentIntentId: 'pi_webhook_1',
            currentPeriodEnd: Carbon::today()->addMonth(),
        ));

        $response = $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('checkout.session.completed', [
            'id' => $transaction->reference, 'mode' => 'subscription', 'payment_status' => 'paid',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $admin->tenant_id, 'status' => 'Active', 'gateway_subscription_id' => 'sub_webhook_1',
        ]);
        $this->assertDatabaseHas('payment_transactions', ['reference' => $transaction->reference, 'status' => 'Paid']);
    }

    public function test_a_duplicate_webhook_event_is_processed_only_once(): void
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
            subscriptionId: 'sub_dup_1', customerId: 'cus_dup_1', paymentIntentId: 'pi_dup_1',
            currentPeriodEnd: Carbon::today()->addMonth(),
        ));

        $payload = $this->webhookPayload('checkout.session.completed', [
            'id' => $transaction->reference, 'mode' => 'subscription', 'payment_status' => 'paid',
        ], eventId: 'evt_fixed_id');

        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertOk();
        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertOk();

        $this->assertSame(1, PaymentWebhookEvent::where('event_id', 'evt_fixed_id')->count());
        $this->assertSame(1, PaymentTransaction::where('reference', $transaction->reference)->where('status', 'Paid')->count());
        $this->assertSame(1, SubscriptionInvoice::where('tenant_id', $admin->tenant_id)->where('status', 'Paid')->count());
    }

    public function test_invoice_payment_succeeded_renews_the_subscription(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $tenant = Tenant::default();
        $plan = $this->paidPlan(monthlyPrice: 79);
        $subscription = TenantSubscription::factory()->active()->create([
            'tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'billing_cycle' => 'Monthly',
            'gateway' => 'stripe', 'gateway_subscription_id' => 'sub_renew_1',
        ]);
        $newPeriodEnd = Carbon::parse($subscription->next_billing_at)->addMonth();
        $gateway->stageSubscription('sub_renew_1', new GatewaySubscription('sub_renew_1', 'active', $newPeriodEnd));

        $response = $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('invoice.payment_succeeded', [
            'id' => 'in_renew_1', 'subscription' => 'sub_renew_1', 'amount_paid' => 7900, 'currency' => 'usd',
            'payment_intent' => 'pi_renew_1',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', ['reference' => 'in_renew_1', 'status' => 'Paid', 'amount' => 79]);
        // The factory-made subscription started with no invoice history — this webhook records the first one.
        $this->assertSame(1, SubscriptionInvoice::where('tenant_subscription_id', $subscription->id)->where('status', 'Paid')->count());
        $this->assertSame($newPeriodEnd->toDateString(), Carbon::parse($subscription->fresh()->next_billing_at)->toDateString());
    }

    public function test_invoice_payment_failed_marks_the_subscription_past_due(): void
    {
        $this->bindFakePaymentGateway();
        $tenant = Tenant::default();
        $subscription = TenantSubscription::factory()->active()->create([
            'tenant_id' => $tenant->id, 'gateway' => 'stripe', 'gateway_subscription_id' => 'sub_fail_1',
        ]);

        $response = $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('invoice.payment_failed', [
            'id' => 'in_fail_1', 'subscription' => 'sub_fail_1', 'amount_due' => 7900, 'currency' => 'usd',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Past Due']);
        $this->assertDatabaseHas('payment_transactions', ['reference' => 'in_fail_1', 'status' => 'Failed']);
    }

    public function test_customer_subscription_deleted_cancels_the_subscription(): void
    {
        $this->bindFakePaymentGateway();
        $tenant = Tenant::default();
        $subscription = TenantSubscription::factory()->active()->create([
            'tenant_id' => $tenant->id, 'gateway' => 'stripe', 'gateway_subscription_id' => 'sub_cancel_1',
        ]);

        $response = $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('customer.subscription.deleted', [
            'id' => 'sub_cancel_1', 'status' => 'canceled',
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('tenant_subscriptions', ['id' => $subscription->id, 'status' => 'Cancelled']);
    }

    public function test_charge_refunded_records_a_refund_and_marks_the_invoice_refunded(): void
    {
        $this->bindFakePaymentGateway();
        $tenant = Tenant::default();
        $invoice = SubscriptionInvoice::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Paid']);
        $charge = PaymentTransaction::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'reference' => 'ch_original_1',
            'gateway_payment_intent_id' => 'pi_original_1',
        ]);

        $response = $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('charge.refunded', [
            'id' => 'ch_original_1', 'payment_intent' => 'pi_original_1', 'amount_refunded' => 7900, 'currency' => 'usd',
            'refunds' => ['data' => [['id' => 're_webhook_1']]],
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 're_webhook_1', 'type' => 'Refund', 'related_reference' => $charge->reference, 'amount' => 79,
        ]);
        $this->assertDatabaseHas('subscription_invoices', ['id' => $invoice->id, 'status' => 'Refunded']);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $gateway->verifySignatureResult = false;

        $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('checkout.session.completed', ['id' => 'cs_bad', 'mode' => 'subscription']))
            ->assertStatus(400);
    }

    public function test_an_unrecognized_event_type_is_accepted_and_ignored(): void
    {
        $this->bindFakePaymentGateway();

        $this->postJson('/api/v1/webhooks/stripe', $this->webhookPayload('customer.updated', ['id' => 'cus_1']))
            ->assertOk();

        $this->assertSame(0, PaymentTransaction::count());
    }
}
