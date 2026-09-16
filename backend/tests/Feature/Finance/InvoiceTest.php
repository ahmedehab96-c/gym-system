<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Member;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_invoices_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        Invoice::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/invoices?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_invoices_by_member_name(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $member = Member::factory()->create(['name' => 'Findable Billed']);
        Invoice::factory()->create(['member_id' => $member->id]);
        Invoice::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/invoices?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_invoices_by_status(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        Invoice::factory()->create(['status' => 'Paid']);
        Invoice::factory()->create(['status' => 'Overdue']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/invoices?status=Overdue');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Overdue');
    }

    public function test_it_creates_an_invoice_with_items_and_computes_totals(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/invoices', [
            'member_id' => $member->id,
            'due_date' => '2026-12-01',
            'discount' => 50,
            'items' => [
                ['description' => 'Monthly Membership Fee', 'amount' => 600],
                ['description' => 'Locker Rental', 'amount' => 100],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.subtotal', 700)
            ->assertJsonPath('data.discount', 50)
            ->assertJsonPath('data.total', 650)
            ->assertJsonPath('data.status', 'Unpaid');
        $this->assertNotEmpty($response->json('data.invoiceNumber'));
        $this->assertNotEmpty($response->json('data.business.name'));
    }

    public function test_creating_an_invoice_requires_at_least_one_item(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/invoices', [
            'member_id' => $member->id,
            'due_date' => '2026-12-01',
            'items' => [],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('items');
    }

    public function test_it_shows_an_invoice_with_print_ready_data(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $invoice = Invoice::factory()->create(['total' => 400]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 400]);
        Payment::factory()->create(['member_id' => $invoice->member_id, 'invoice_id' => $invoice->id, 'amount' => 150, 'status' => 'Paid']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('data.amountPaid', 150)
            ->assertJsonPath('data.balanceDue', 250)
            ->assertJsonStructure(['data' => ['business' => ['name', 'currency']]]);
    }

    public function test_it_updates_an_invoice_and_replaces_items(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $invoice = Invoice::factory()->create(['discount' => 0]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 200]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/invoices/{$invoice->id}", [
            'items' => [
                ['description' => 'Replacement Item', 'amount' => 500],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.subtotal', 500)
            ->assertJsonPath('data.total', 500);
        $this->assertSame(1, $invoice->items()->count());
    }

    public function test_updating_discount_recomputes_total(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $invoice = Invoice::factory()->create(['discount' => 0]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 300]);
        $invoice->update(['total' => 300]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/invoices/{$invoice->id}", [
            'discount' => 100,
        ]);

        $response->assertOk()->assertJsonPath('data.total', 200);
    }

    public function test_it_deletes_an_invoice(): void
    {
        $user = $this->userWithFullAccess(['Invoices']);
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Invoices',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/invoices');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_invoices(): void
    {
        $response = $this->getJson('/api/v1/invoices');

        $response->assertStatus(401);
    }
}
