<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_checks_in_an_active_member(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
            'method' => 'QR Code',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.memberId', $member->id)
            ->assertJsonPath('data.status', 'Checked In')
            ->assertJsonPath('data.method', 'QR Code');

        $this->assertDatabaseHas('attendance_records', ['member_id' => $member->id, 'check_out' => null]);
    }

    public function test_it_prevents_a_duplicate_active_check_in(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);
        AttendanceRecord::factory()->create(['member_id' => $member->id, 'check_out' => null]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, AttendanceRecord::where('member_id', $member->id)->count());
    }

    public function test_it_allows_check_in_after_a_previous_visit_was_checked_out(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);
        AttendanceRecord::factory()->create(['member_id' => $member->id, 'check_out' => '9:00']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ]);

        $response->assertCreated();
    }

    public function test_it_rejects_check_in_for_a_suspended_member(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Suspended']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('attendance_records', ['member_id' => $member->id]);
    }

    public function test_it_rejects_check_in_for_an_expired_member(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Expired']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_check_in_for_an_inactive_member(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Inactive']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_check_in_requires_a_valid_member(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/attendance/check-in', []);

        $response->assertStatus(422)->assertJsonValidationErrors('member_id');
    }

    public function test_it_checks_out_an_active_record(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);
        $record = AttendanceRecord::factory()->create([
            'member_id' => $member->id,
            'check_in' => '6:00',
            'check_out' => null,
            'duration' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/attendance/{$record->id}/check-out");

        $response->assertOk()->assertJsonPath('data.status', 'Checked Out');
        $this->assertNotNull($response->json('data.checkOut'));
        $this->assertNotNull($response->json('data.duration'));
    }

    public function test_it_refuses_to_check_out_an_already_checked_out_record(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $record = AttendanceRecord::factory()->create(['check_out' => '10:00']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/attendance/{$record->id}/check-out");

        $response->assertStatus(422);
    }

    public function test_it_lists_todays_attendance(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->toDateString()]);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->subDay()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance/today');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_attendance_by_date_member_and_status(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create();
        $target = AttendanceRecord::factory()->create([
            'member_id' => $member->id,
            'date' => Carbon::today()->toDateString(),
            'check_out' => null,
        ]);
        AttendanceRecord::factory()->create([
            'date' => Carbon::today()->toDateString(),
            'check_out' => '10:00',
        ]);
        AttendanceRecord::factory()->create([
            'member_id' => $member->id,
            'date' => Carbon::today()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            '/api/v1/attendance?date='.Carbon::today()->toDateString()."&member_id={$member->id}&status=Checked In"
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id);
    }

    public function test_it_searches_attendance_by_member_name(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['name' => 'Searchable Person']);
        AttendanceRecord::factory()->create(['member_id' => $member->id]);
        AttendanceRecord::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance?search=Searchable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_paginates_attendance_records(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        AttendanceRecord::factory()->count(15)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_returns_member_attendance_history(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create();
        AttendanceRecord::factory()->count(3)->create(['member_id' => $member->id]);
        AttendanceRecord::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/members/{$member->id}/attendance");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_returns_attendance_statistics(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->toDateString(), 'check_out' => null]);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->toDateString(), 'check_out' => '10:00']);
        AttendanceRecord::factory()->create(['date' => Carbon::today()->subDays(3)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance/stats');

        $response->assertOk()
            ->assertJsonPath('data.today.presentToday', 2)
            ->assertJsonPath('data.today.checkedIn', 1)
            ->assertJsonPath('data.today.checkedOut', 1)
            ->assertJsonCount(14, 'data.daily')
            ->assertJsonCount(7, 'data.weekly')
            ->assertJsonCount(12, 'data.monthly');
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant',
            'module' => 'Attendance',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/attendance');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_attendance(): void
    {
        $response = $this->getJson('/api/v1/attendance');

        $response->assertStatus(401);
    }
}
