<?php

namespace Tests\Feature\Finance;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_a_financial_overview(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $today = Carbon::today();

        Payment::factory()->create(['status' => 'Paid', 'method' => 'Cash', 'date' => $today->toDateString(), 'amount' => 100]);
        Payment::factory()->create(['status' => 'Paid', 'method' => 'Card', 'date' => $today->copy()->subDays(10)->toDateString(), 'amount' => 200]);
        Payment::factory()->create(['status' => 'Pending', 'amount' => 50]);
        Payment::factory()->create(['status' => 'Refunded', 'amount' => 75]);
        Expense::factory()->create(['amount' => 120]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/finance/overview');

        $response->assertOk()
            ->assertJsonPath('data.daily', 100)
            ->assertJsonPath('data.totalRevenue', 300)
            ->assertJsonPath('data.totalExpenses', 120)
            ->assertJsonPath('data.netRevenue', 180)
            ->assertJsonPath('data.pendingPayments.count', 1)
            ->assertJsonPath('data.pendingPayments.amount', 50)
            ->assertJsonPath('data.refunds.count', 1)
            ->assertJsonPath('data.refunds.amount', 75);

        $byMethod = collect($response->json('data.revenueByMethod'))->keyBy('method');
        $this->assertSame(100, $byMethod['Cash']['amount']);
        $this->assertSame(200, $byMethod['Card']['amount']);
    }

    public function test_it_returns_revenue_over_time_matching_the_dashboard_chart_shape(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $thisMonth = Carbon::today()->startOfMonth();

        Payment::factory()->create(['status' => 'Paid', 'date' => $thisMonth->toDateString(), 'amount' => 500]);
        Expense::factory()->create(['date' => $thisMonth->toDateString(), 'amount' => 200]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/finance/revenue-over-time?months=6');

        $response->assertOk()->assertJsonCount(6, 'data');
        $lastMonth = collect($response->json('data'))->last();
        $this->assertSame(500, $lastMonth['revenue']);
        $this->assertSame(200, $lastMonth['expenses']);
        $this->assertArrayHasKey('month', $lastMonth);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Reports',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/finance/overview');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_finance_endpoints(): void
    {
        $response = $this->getJson('/api/v1/finance/overview');

        $response->assertStatus(401);
    }
}
