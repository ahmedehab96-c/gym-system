<?php

namespace Tests\Feature\Analytics;

use App\Models\AttendanceRecord;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\GymClass;
use App\Models\MaintenanceRecord;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_member_growth_with_a_running_total(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $thisMonth = Carbon::today()->startOfMonth();

        Member::factory()->create(['join_date' => $thisMonth->copy()->subMonths(3)->toDateString()]);
        Member::factory()->create(['join_date' => $thisMonth->toDateString()]);
        Member::factory()->create(['join_date' => $thisMonth->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/member-growth?months=6');

        $response->assertOk()->assertJsonCount(6, 'data');
        $last = collect($response->json('data'))->last();
        $this->assertSame(3, $last['members']);
        $this->assertSame(2, $last['newMembers']);
    }

    public function test_it_returns_attendance_trends_for_the_requested_window(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->toDateString()]);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/attendance-trends?days=7');

        $response->assertOk()->assertJsonCount(7, 'data');
        $last = collect($response->json('data'))->last();
        $this->assertSame(2, $last['visits']);
    }

    public function test_it_returns_revenue_and_expense_trends(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $thisMonth = Carbon::today()->startOfMonth();

        Payment::factory()->create(['status' => 'Paid', 'date' => $thisMonth->toDateString(), 'amount' => 500]);
        Expense::factory()->create(['date' => $thisMonth->toDateString(), 'amount' => 200]);

        $revenue = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/revenue-trends?months=3');
        $expenses = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/expense-trends?months=3');
        $net = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/net-revenue?months=3');

        $revenue->assertOk();
        $this->assertSame(500, collect($revenue->json('data'))->last()['revenue']);

        $expenses->assertOk();
        $this->assertSame(200, collect($expenses->json('data'))->last()['expenses']);

        $net->assertOk()
            ->assertJsonPath('data.totalRevenue', 500)
            ->assertJsonPath('data.totalExpenses', 200)
            ->assertJsonPath('data.netRevenue', 300);
        $this->assertSame(300, collect($net->json('data.trend'))->last()['net']);
    }

    public function test_it_returns_membership_distribution_by_status(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Membership::factory()->create(['status' => 'Active']);
        Membership::factory()->create(['status' => 'Active']);
        Membership::factory()->create(['status' => 'Expired']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/membership-distribution');

        $response->assertOk();
        $byName = collect($response->json('data'))->keyBy('name');
        $this->assertSame(2, $byName['Active']['value']);
        $this->assertSame(1, $byName['Expired']['value']);
        $this->assertSame(0, $byName['Suspended']['value']);
        $this->assertSame('#22c55e', $byName['Active']['color']);
    }

    public function test_it_returns_revenue_by_payment_method(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Payment::factory()->create(['status' => 'Paid', 'method' => 'Cash', 'amount' => 100]);
        Payment::factory()->create(['status' => 'Paid', 'method' => 'Card', 'amount' => 200]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/revenue-by-method');

        $response->assertOk();
        $byMethod = collect($response->json('data'))->keyBy('method');
        $this->assertSame(100, $byMethod['Cash']['amount']);
        $this->assertSame(200, $byMethod['Card']['amount']);
    }

    public function test_it_returns_class_performance_with_fill_rate(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $class = GymClass::factory()->create(['capacity' => 10]);
        Member::factory(5)->create()->each(fn (Member $member) => $class->bookings()->create([
            'member_id' => $member->id,
            'booked_at' => now(),
        ]));

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/class-performance');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $class->id);
        $this->assertSame(5, $row['booked']);
        $this->assertSame(50, $row['fillRate']);
    }

    public function test_it_returns_trainer_performance(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $trainer = Trainer::factory()->create(['sessions_completed' => 42, 'rating' => 4.5]);
        Member::factory()->create(['trainer_id' => $trainer->id]);
        Member::factory()->create(['trainer_id' => $trainer->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/trainer-performance');

        $response->assertOk();
        $row = collect($response->json('data'))->firstWhere('id', $trainer->id);
        $this->assertSame(42, $row['sessionsCompleted']);
        $this->assertSame(2, $row['assignedMembers']);
    }

    public function test_it_returns_equipment_and_maintenance_statistics(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $equipment1 = Equipment::factory()->create(['status' => 'In Use', 'condition' => 'Good']);
        $equipment2 = Equipment::factory()->create(['status' => 'Under Maintenance', 'condition' => 'Needs Maintenance']);
        MaintenanceRecord::factory()->create(['equipment_id' => $equipment1->id, 'status' => 'Completed', 'cost' => 300]);
        MaintenanceRecord::factory()->create([
            'equipment_id' => $equipment2->id,
            'status' => 'Upcoming',
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'cost' => 150,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/equipment-stats');

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.maintenance.completed', 1)
            ->assertJsonPath('data.maintenance.upcoming', 1)
            ->assertJsonPath('data.maintenance.totalCost', 450);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Reports',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/analytics/member-growth');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_analytics_endpoints(): void
    {
        $response = $this->getJson('/api/v1/analytics/member-growth');
        $response->assertStatus(401);
    }
}
