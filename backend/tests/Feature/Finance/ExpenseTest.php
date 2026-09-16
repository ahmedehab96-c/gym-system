<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_expenses_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        Expense::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_expenses_by_title_or_vendor(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        Expense::factory()->create(['title' => 'Findable Expense']);
        Expense::factory()->create(['title' => 'Other']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_expenses_by_category_and_date_range(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        $match = Expense::factory()->create(['category' => 'Rent', 'date' => '2026-06-15']);
        Expense::factory()->create(['category' => 'Utilities', 'date' => '2026-06-15']);
        Expense::factory()->create(['category' => 'Rent', 'date' => '2026-01-01']);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            '/api/v1/expenses?category=Rent&date_from=2026-06-01&date_to=2026-06-30'
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_creates_an_expense(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', [
            'title' => 'New Equipment',
            'category' => 'Equipment',
            'amount' => 5000,
            'vendor' => 'Rogue',
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'New Equipment')->assertJsonPath('data.amount', 5000);
    }

    public function test_creating_an_expense_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/expenses', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['title', 'category', 'amount']);
    }

    public function test_it_updates_an_expense(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        $expense = Expense::factory()->create(['amount' => 100]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/expenses/{$expense->id}", [
            'amount' => 200,
        ]);

        $response->assertOk()->assertJsonPath('data.amount', 200);
    }

    public function test_it_deletes_an_expense(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        $expense = Expense::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/expenses/{$expense->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_it_uploads_a_receipt_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Expenses']);
        $expense = Expense::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/expenses/{$expense->id}/receipt", [
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('/storage/receipts/', $response->json('data.receipt'));
        $this->assertCount(1, Storage::disk('public')->files('receipts'));
    }

    public function test_uploading_an_unsupported_receipt_type_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Expenses']);
        $expense = Expense::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/expenses/{$expense->id}/receipt", [
            'receipt' => UploadedFile::fake()->create('receipt.exe', 100, 'application/octet-stream'),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_returns_monthly_expense_statistics(): void
    {
        $user = $this->userWithFullAccess(['Expenses']);
        Expense::factory()->create(['category' => 'Rent', 'amount' => 1000, 'date' => Carbon::today()->toDateString()]);
        Expense::factory()->create(['category' => 'Rent', 'amount' => 500, 'date' => Carbon::today()->subMonths(2)->toDateString()]);
        Expense::factory()->create(['category' => 'Utilities', 'amount' => 300, 'date' => Carbon::today()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses/stats');

        $response->assertOk()
            ->assertJsonPath('data.total', 1800)
            ->assertJsonPath('data.thisMonth', 1300)
            ->assertJsonPath('data.largestCategory', 'Rent');
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Expenses',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/expenses');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_expenses(): void
    {
        $response = $this->getJson('/api/v1/expenses');

        $response->assertStatus(401);
    }
}
