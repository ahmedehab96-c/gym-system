<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class QrAttendanceTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_checks_in_a_member_from_a_valid_qr_token(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $member->issueQrToken();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertCreated()
            ->assertJsonPath('data.member.id', $member->id)
            ->assertJsonPath('data.attendance.status', 'Checked In')
            ->assertJsonPath('data.attendance.method', 'QR Code')
            ->assertJsonPath('data.duplicate', false);
        $this->assertDatabaseHas('attendance_records', ['member_id' => $member->id, 'check_out' => null, 'method' => 'QR Code']);
    }

    public function test_an_invalid_qr_token_is_rejected(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', [
            'token' => str_repeat('x', 48),
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_an_expired_qr_token_is_rejected(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $member->issueQrToken();
        $member->forceFill(['qr_token_expires_at' => now()->subDay()])->save();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_suspended_members_qr_is_rejected(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Suspended', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $member->issueQrToken();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(422)->assertJsonFragment(['message' => "This member's membership is Suspended and cannot check in."]);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_an_expired_memberships_qr_is_rejected_even_if_the_status_column_is_stale(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        // Status column says Active but the expiry date has already passed
        // — MembershipStatus::resolveMemberStatus() must catch this even
        // before the daily UpdateMembershipStatuses command would.
        $member = Member::factory()->create(['status' => 'Active', 'expiry_date' => now()->subDay()->toDateString()]);
        $token = $member->issueQrToken();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(422)->assertJsonFragment(['message' => "This member's membership is Expired and cannot check in."]);
    }

    public function test_a_duplicate_scan_of_an_already_checked_in_member_is_an_idempotent_success(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $member->issueQrToken();
        $existing = AttendanceRecord::factory()->create(['member_id' => $member->id, 'check_out' => null]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertOk()
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.attendance.id', $existing->id);
        $this->assertSame(1, AttendanceRecord::where('member_id', $member->id)->count());
    }

    public function test_it_checks_out_a_member_from_a_qr_token(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);
        $token = $member->issueQrToken();
        $record = AttendanceRecord::factory()->create(['member_id' => $member->id, 'check_in' => '6:00', 'check_out' => null, 'duration' => null]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-out', ['token' => $token]);

        $response->assertOk()->assertJsonPath('data.attendance.id', $record->id)->assertJsonPath('data.attendance.status', 'Checked Out');
        $this->assertNotNull($response->json('data.attendance.duration'));
    }

    public function test_checking_out_a_member_with_no_active_check_in_is_rejected(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);
        $member = Member::factory()->create(['status' => 'Active']);
        $token = $member->issueQrToken();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-out', ['token' => $token]);

        $response->assertStatus(422);
    }

    public function test_a_qr_token_never_resolves_across_tenants(): void
    {
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $staffA = $this->userWithFullAccess(['Attendance'], tenantId: $tenantA->id);
        $memberB = Member::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'Active', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $memberB->issueQrToken();

        $response = $this->actingAs($staffA, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(404);
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_role_without_attendance_permission_cannot_scan(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant', 'module' => 'Attendance',
            'can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);
        $member = Member::factory()->create(['status' => 'Active', 'expiry_date' => now()->addMonths(3)->toDateString()]);
        $token = $member->issueQrToken();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(403);
    }

    public function test_guests_cannot_scan(): void
    {
        $member = Member::factory()->create();
        $token = $member->issueQrToken();

        $response = $this->postJson('/api/v1/qr/check-in', ['token' => $token]);

        $response->assertStatus(401);
    }

    public function test_a_missing_token_fails_validation(): void
    {
        $user = $this->userWithFullAccess(['Attendance']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/qr/check-in', []);

        $response->assertStatus(422)->assertJsonValidationErrors('token');
    }
}
