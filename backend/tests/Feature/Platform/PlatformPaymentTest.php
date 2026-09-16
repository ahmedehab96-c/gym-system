<?php

namespace Tests\Feature\Platform;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPayments;
use Tests\TestCase;

class PlatformPaymentTest extends TestCase
{
    use InteractsWithPayments, RefreshDatabase;

    public function test_a_platform_admin_can_list_transactions_across_every_tenant(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        PaymentTransaction::factory()->paid()->create(['tenant_id' => Tenant::factory()]);
        PaymentTransaction::factory()->create(['tenant_id' => Tenant::factory(), 'status' => 'Failed']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing/transactions');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_transactions_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        PaymentTransaction::factory()->paid()->create(['tenant_id' => Tenant::factory()]);
        PaymentTransaction::factory()->create(['tenant_id' => Tenant::factory(), 'status' => 'Failed']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing/transactions?status=Failed');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Failed');
    }

    public function test_a_platform_admin_can_view_revenue_stats(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $plan = $this->paidPlan(monthlyPrice: 79);
        $tenant = Tenant::factory()->create();
        $subscription = TenantSubscription::factory()->active()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id, 'billing_cycle' => 'Monthly']);
        $invoice = SubscriptionInvoice::factory()->create([
            'tenant_id' => $tenant->id, 'tenant_subscription_id' => $subscription->id, 'plan_id' => $plan->id, 'status' => 'Paid', 'amount' => 79,
        ]);
        PaymentTransaction::factory()->paid()->create(['tenant_id' => $tenant->id, 'subscription_invoice_id' => $invoice->id, 'amount' => 79]);
        PaymentTransaction::factory()->create(['tenant_id' => Tenant::factory(), 'status' => 'Failed']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/platform/billing/stats');

        $response->assertOk()
            ->assertJsonPath('data.totalRevenue', 79)
            ->assertJsonPath('data.successfulPayments', 1)
            ->assertJsonPath('data.failedPayments', 1);
    }

    public function test_a_platform_admin_can_refund_a_paid_transaction(): void
    {
        $gateway = $this->bindFakePaymentGateway();
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create();
        $invoice = SubscriptionInvoice::factory()->create(['tenant_id' => $tenant->id, 'status' => 'Paid', 'amount' => 79]);
        $transaction = PaymentTransaction::factory()->paid()->create([
            'tenant_id' => $tenant->id, 'subscription_invoice_id' => $invoice->id,
            'amount' => 79, 'gateway_payment_intent_id' => 'pi_refund_test',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/billing/transactions/{$transaction->id}/refund");

        $response->assertOk()->assertJsonPath('data.type', 'Refund')->assertJsonPath('data.status', 'Paid');
        $this->assertDatabaseHas('subscription_invoices', ['id' => $invoice->id, 'status' => 'Refunded']);
        $this->assertCount(1, $gateway->calls);
        $this->assertSame('refund', $gateway->calls[0]['method']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.refund_issued', 'entity_id' => $transaction->id]);
    }

    public function test_a_transaction_cannot_be_refunded_twice(): void
    {
        $this->bindFakePaymentGateway();
        $admin = User::factory()->platformAdmin()->create();
        $tenant = Tenant::factory()->create();
        $transaction = PaymentTransaction::factory()->paid()->create([
            'tenant_id' => $tenant->id, 'gateway_payment_intent_id' => 'pi_refund_twice',
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/billing/transactions/{$transaction->id}/refund")->assertOk();
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/billing/transactions/{$transaction->id}/refund")->assertStatus(422);

        $this->assertSame(1, PaymentTransaction::where('type', 'Refund')->count());
    }

    public function test_a_failed_transaction_cannot_be_refunded(): void
    {
        $this->bindFakePaymentGateway();
        $admin = User::factory()->platformAdmin()->create();
        $transaction = PaymentTransaction::factory()->create([
            'tenant_id' => Tenant::factory(), 'status' => 'Failed', 'gateway_payment_intent_id' => 'pi_failed',
        ]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/billing/transactions/{$transaction->id}/refund")->assertStatus(422);
    }

    public function test_a_regular_tenant_admin_cannot_issue_refunds(): void
    {
        $this->bindFakePaymentGateway();
        $tenant = Tenant::default();
        $admin = User::factory()->create(['role' => 'Super Admin', 'tenant_id' => $tenant->id]);
        $transaction = PaymentTransaction::factory()->paid()->create(['tenant_id' => $tenant->id, 'gateway_payment_intent_id' => 'pi_unauth']);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/platform/billing/transactions/{$transaction->id}/refund")->assertStatus(403);
    }

    public function test_a_guest_cannot_access_platform_payment_endpoints(): void
    {
        $this->getJson('/api/v1/platform/billing/transactions')->assertStatus(401);
        $this->getJson('/api/v1/platform/billing/stats')->assertStatus(401);
    }
}
