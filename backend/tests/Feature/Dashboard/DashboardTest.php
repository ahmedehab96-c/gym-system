<?php

namespace Tests\Feature\Dashboard;

use App\Models\AttendanceRecord;
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

class DashboardTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_returns_dashboard_summary_stats_matching_seeded_data(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $today = Carbon::today();

        $active1 = Member::factory()->create(['status' => 'Active', 'join_date' => $today->toDateString()]);
        $active2 = Member::factory()->create(['status' => 'Active', 'join_date' => $today->copy()->subDays(60)->toDateString()]);
        $inactive = Member::factory()->create(['status' => 'Inactive', 'join_date' => $today->copy()->subDays(60)->toDateString()]);

        Membership::factory()->create(['member_id' => $inactive->id, 'status' => 'Expired']);
        Membership::factory()->create([
            'member_id' => $active1->id,
            'status' => 'Expiring Soon',
            'expiry_date' => $today->copy()->addDays(3)->toDateString(),
        ]);

        AttendanceRecord::factory()->create(['member_id' => $active1->id, 'date' => $today->toDateString()]);
        AttendanceRecord::factory()->create(['member_id' => $active2->id, 'date' => $today->copy()->subDay()->toDateString()]);

        Payment::factory()->create(['member_id' => $active1->id, 'status' => 'Paid', 'date' => $today->toDateString(), 'amount' => 500]);
        Payment::factory()->create(['member_id' => $active2->id, 'status' => 'Paid', 'date' => $today->copy()->subMonths(2)->toDateString(), 'amount' => 999]);
        Payment::factory()->create(['member_id' => $inactive->id, 'status' => 'Pending', 'amount' => 100]);

        Trainer::factory()->create(['status' => 'Active']);
        Trainer::factory()->create(['status' => 'Inactive']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/summary');

        $response->assertOk()
            ->assertJsonPath('data.stats.totalMembers', 3)
            ->assertJsonPath('data.stats.activeMembers', 2)
            ->assertJsonPath('data.stats.newMembers', 1)
            ->assertJsonPath('data.stats.expiredMemberships', 1)
            ->assertJsonPath('data.stats.expiringMemberships', 1)
            ->assertJsonPath('data.stats.todayAttendance', 1)
            ->assertJsonPath('data.stats.monthlyRevenue', 500)
            ->assertJsonPath('data.stats.pendingPayments', 1)
            ->assertJsonPath('data.stats.activeTrainers', 1);

        $response->assertJsonCount(1, 'data.expiringMemberships');
    }

    public function test_it_returns_recent_lists_and_upcoming_classes(): void
    {
        $user = $this->userWithFullAccess(['Reports']);
        $today = Carbon::today();

        $member = Member::factory()->create(['name' => 'Latest Member', 'join_date' => $today->toDateString()]);
        Payment::factory()->create(['member_id' => $member->id, 'status' => 'Paid', 'date' => $today->toDateString()]);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create([
            'trainer_id' => $trainer->id,
            'date' => $today->copy()->addDay()->toDateString(),
            'status' => 'Scheduled',
        ]);
        GymClass::factory()->create([
            'trainer_id' => $trainer->id,
            'date' => $today->copy()->subDay()->toDateString(),
            'status' => 'Completed',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/summary');

        $response->assertOk()
            ->assertJsonPath('data.recentMembers.0.name', 'Latest Member')
            ->assertJsonCount(1, 'data.upcomingClasses');
    }

    public function test_it_returns_a_merged_recent_activity_feed(): void
    {
        $user = $this->userWithFullAccess(['Reports']);

        Member::factory()->create(['join_date' => Carbon::today()->toDateString()]);
        Payment::factory()->create(['status' => 'Paid', 'date' => Carbon::today()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/activity?limit=5');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data'));
        $this->assertArrayHasKey('type', $response->json('data.0'));
        $this->assertArrayHasKey('date', $response->json('data.0'));
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer', 'module' => 'Reports',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/dashboard/summary');
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_dashboard_endpoints(): void
    {
        $response = $this->getJson('/api/v1/dashboard/summary');
        $response->assertStatus(401);
    }
}
