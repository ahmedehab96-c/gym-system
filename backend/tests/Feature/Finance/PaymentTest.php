<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Member;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_payments_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        Payment::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_payments_by_member_name(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create(['name' => 'Findable Payer']);
        Payment::factory()->create(['member_id' => $member->id]);
        Payment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_payments_by_status_and_method(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $match = Payment::factory()->create(['status' => 'Paid', 'method' => 'Card']);
        Payment::factory()->create(['status' => 'Pending', 'method' => 'Card']);
        Payment::factory()->create(['status' => 'Paid', 'method' => 'Cash']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments?status=Paid&method=Card');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_filters_payments_by_date_range(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $match = Payment::factory()->create(['date' => '2026-06-15']);
        Payment::factory()->create(['date' => '2026-01-01']);
        Payment::factory()->create(['date' => '2026-12-31']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments?date_from=2026-06-01&date_to=2026-06-30');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_creates_a_payment_with_a_reference(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'member_id' => $member->id,
            'amount' => 500,
            'method' => 'Card',
            'status' => 'Paid',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', 500)
            ->assertJsonPath('data.status', 'Paid')
            ->assertJsonPath('data.memberName', $member->name);
        $this->assertNotEmpty($response->json('data.reference'));
    }

    public function test_creating_a_payment_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Payments']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['member_id', 'amount']);
    }

    public function test_paying_the_full_invoice_amount_marks_it_paid(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create();
        $invoice = Invoice::factory()->create(['member_id' => $member->id, 'total' => 500, 'status' => 'Unpaid']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 500]);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/payments', [
            'member_id' => $member->id,
            'invoice_id' => $invoice->id,
            'amount' => 500,
            'status' => 'Paid',
        ])->assertCreated();

        $this->assertSame('Paid', $invoice->fresh()->status);
    }

    public function test_it_updates_a_payment(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $payment = Payment::factory()->create(['amount' => 100]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/payments/{$payment->id}", [
            'amount' => 250,
        ]);

        $response->assertOk()->assertJsonPath('data.amount', 250);
    }

    public function test_it_deletes_a_payment(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $payment = Payment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_it_refunds_a_paid_payment_and_reverts_the_invoice(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create();
        $invoice = Invoice::factory()->create(['member_id' => $member->id, 'total' => 300, 'status' => 'Paid', 'due_date' => Carbon::today()->addDays(10)]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 300]);
        $payment = Payment::factory()->create(['member_id' => $member->id, 'invoice_id' => $invoice->id, 'amount' => 300, 'status' => 'Paid']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/payments/{$payment->id}/refund");

        $response->assertOk()->assertJsonPath('data.status', 'Refunded');
        $this->assertSame('Unpaid', $invoice->fresh()->status);
    }

    public function test_it_refuses_to_refund_a_non_paid_payment(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $payment = Payment::factory()->create(['status' => 'Pending']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/payments/{$payment->id}/refund");

        $response->assertStatus(422);
    }

    public function test_it_lists_payment_history_for_a_member(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create();
        Payment::factory()->count(3)->create(['member_id' => $member->id]);
        Payment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/members/{$member->id}/payments");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_returns_payment_statistics(): void
    {
        $user = $this->userWithFullAccess(['Payments']);
        Payment::factory()->create(['status' => 'Paid', 'date' => Carbon::today()->toDateString(), 'amount' => 100]);
        Payment::factory()->create(['status' => 'Pending', 'amount' => 50]);
        Payment::factory()->create(['status' => 'Refunded', 'amount' => 25]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments/stats');

        $response->assertOk()
            ->assertJsonPath('data.todayRevenue', 100)
            ->assertJsonPath('data.pending', 1)
            ->assertJsonPath('data.refunds', 1);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Payments',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/payments');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertStatus(401);
    }
}
