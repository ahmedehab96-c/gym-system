<?php

namespace Tests\Feature\Reports;

use App\Models\AttendanceRecord;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\GymClass;
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

class ReportTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_members_report_returns_summary_chart_and_paginated_data(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $today = Carbon::today();

        Member::factory()->create(['name' => 'Ziad Karim', 'status' => 'Active', 'join_date' => $today->toDateString()]);
        Member::factory()->create(['status' => 'Active', 'join_date' => $today->copy()->subDays(40)->toDateString()]);
        Member::factory()->create(['status' => 'Expired', 'join_date' => $today->copy()->subDays(40)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/members?per_page=2');

        $response->assertOk()
            ->assertJsonPath('data.summary.total', 3)
            ->assertJsonPath('data.summary.active', 2)
            ->assertJsonPath('data.summary.expired', 1)
            ->assertJsonPath('data.meta.total', 3)
            ->assertJsonCount(2, 'data.data');

        $this->assertNotEmpty($response->json('data.chart'));

        $searchResponse = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/members?search=Ziad');
        $searchResponse->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.name', 'Ziad Karim');
    }

    public function test_membership_report_filters_by_status_and_computes_total_value(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Membership::factory()->create(['status' => 'Active', 'price' => 600, 'start_date' => Carbon::today()->toDateString()]);
        Membership::factory()->create(['status' => 'Expired', 'price' => 300, 'start_date' => Carbon::today()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/memberships?status=Active');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.totalValue', 600);
    }

    public function test_attendance_report_groups_visits_daily_by_default(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $today = Carbon::today();

        AttendanceRecord::factory()->create(['date' => $today->toDateString()]);
        AttendanceRecord::factory()->create(['date' => $today->toDateString()]);
        AttendanceRecord::factory()->create(['date' => $today->copy()->subDays(5)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/attendance');

        $response->assertOk()->assertJsonPath('data.summary.totalVisits', 3);

        $todayBucket = collect($response->json('data.chart'))->firstWhere('period', $today->toDateString());
        $this->assertSame(2, $todayBucket['value']);
    }

    public function test_revenue_report_defaults_to_paid_payments_and_supports_grouping(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $thisMonth = Carbon::today()->startOfMonth();

        Payment::factory()->create(['status' => 'Paid', 'method' => 'Cash', 'date' => $thisMonth->toDateString(), 'amount' => 500]);
        Payment::factory()->create(['status' => 'Pending', 'date' => $thisMonth->toDateString(), 'amount' => 999]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/revenue?group=monthly&months=3');

        $response->assertOk()
            ->assertJsonPath('data.summary.total', 500)
            ->assertJsonPath('data.summary.count', 1)
            ->assertJsonCount(1, 'data.data');

        $byMethod = collect($response->json('data.summary.byMethod'));
        $this->assertSame(500, $byMethod->firstWhere('method', 'Cash')['amount']);
    }

    public function test_expense_report_breaks_down_by_category(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Expense::factory()->create(['category' => 'Rent', 'amount' => 1000, 'date' => Carbon::today()->toDateString()]);
        Expense::factory()->create(['category' => 'Utilities', 'amount' => 250, 'date' => Carbon::today()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/expenses');

        $response->assertOk()->assertJsonPath('data.summary.total', 1250);

        $byCategory = collect($response->json('data.summary.byCategory'));
        $this->assertSame(1000, $byCategory->firstWhere('category', 'Rent')['amount']);
    }

    public function test_trainer_report_returns_summary_and_records(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Trainer::factory()->create(['status' => 'Active', 'rating' => 4.8]);
        Trainer::factory()->create(['status' => 'Inactive', 'rating' => 3.5]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/trainers');

        $response->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.active', 1)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_class_report_computes_bookings_and_fill_rate(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $class = GymClass::factory()->create(['capacity' => 10, 'date' => Carbon::today()->toDateString(), 'status' => 'Scheduled']);
        Member::factory(4)->create()->each(fn (Member $member) => $class->bookings()->create([
            'member_id' => $member->id,
            'booked_at' => now(),
        ]));

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/classes');

        $response->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.totalBookings', 4)
            ->assertJsonPath('data.summary.averageFillRate', 40);
    }

    public function test_equipment_report_breaks_down_by_status_and_condition(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        Equipment::factory()->create(['status' => 'In Use', 'condition' => 'Good']);
        Equipment::factory()->create(['status' => 'Retired', 'condition' => 'Out of Service']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/equipment');

        $response->assertOk()->assertJsonPath('data.summary.total', 2);
        $this->assertSame(1, $response->json('data.summary.byStatus.In Use'));
        $this->assertSame(1, $response->json('data.summary.byStatus.Retired'));
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Reports',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/reports/members');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_report_endpoints(): void
    {
        $response = $this->getJson('/api/v1/reports/members');
        $response->assertStatus(401);
    }
}
