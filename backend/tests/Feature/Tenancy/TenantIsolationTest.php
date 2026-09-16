<?php

namespace Tests\Feature\Tenancy;

use App\Models\AttendanceRecord;
use App\Models\GymSetting;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_creates_a_tenant_with_the_expected_defaults(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Iron Paradise', 'status' => 'Trial']);

        $this->assertDatabaseHas('tenants', ['name' => 'Iron Paradise', 'status' => 'Trial']);
        $this->assertNotNull($tenant->slug);
    }

    public function test_a_user_belongs_to_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue($user->tenant->is($tenant));
        $this->assertFalse($user->isPlatformAdmin());
    }

    public function test_a_platform_admin_has_no_tenant(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->assertNull($admin->tenant_id);
        $this->assertTrue($admin->isPlatformAdmin());
    }

    public function test_members_index_only_returns_the_authenticated_users_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Members'], 'Super Admin', $tenantA->id);
        Member::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
        Member::factory()->count(5)->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/members?per_page=50');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_tenant_cannot_fetch_another_tenants_member_by_id(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Members'], 'Super Admin', $tenantA->id);
        $memberB = Member::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($userA, 'sanctum')->getJson("/api/v1/members/{$memberB->id}");

        $response->assertStatus(404);
    }

    public function test_a_tenant_cannot_update_or_delete_another_tenants_member(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Members'], 'Super Admin', $tenantA->id);
        $memberB = Member::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userA, 'sanctum')->putJson("/api/v1/members/{$memberB->id}", ['name' => 'Hijacked'])
            ->assertStatus(404);
        $this->actingAs($userA, 'sanctum')->deleteJson("/api/v1/members/{$memberB->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('members', ['id' => $memberB->id, 'name' => $memberB->name]);
    }

    public function test_a_tenant_cannot_fetch_another_tenants_payment_or_invoice(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Payments', 'Invoices'], 'Super Admin', $tenantA->id);
        $paymentB = Payment::factory()->create(['tenant_id' => $tenantB->id]);
        $invoiceB = Invoice::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($userA, 'sanctum')->getJson("/api/v1/payments/{$paymentB->id}")->assertStatus(404);
        $this->actingAs($userA, 'sanctum')->getJson("/api/v1/invoices/{$invoiceB->id}")->assertStatus(404);
    }

    public function test_a_tenant_cannot_check_out_another_tenants_attendance_record(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Attendance'], 'Super Admin', $tenantA->id);
        $attendanceB = AttendanceRecord::factory()->create(['tenant_id' => $tenantB->id, 'check_out' => null]);

        $response = $this->actingAs($userA, 'sanctum')->postJson("/api/v1/attendance/{$attendanceB->id}/check-out");

        $response->assertStatus(404);
        $this->assertDatabaseHas('attendance_records', ['id' => $attendanceB->id, 'check_out' => null]);
    }

    public function test_a_tenant_cannot_fetch_another_tenants_staff_member(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Staff'], 'Super Admin', $tenantA->id);
        $staffB = User::factory()->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($userA, 'sanctum')->getJson("/api/v1/staff/{$staffB->id}");

        $response->assertStatus(404);
    }

    public function test_staff_index_only_lists_the_authenticated_users_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        $userA = $this->userWithFullAccess(['Staff'], 'Super Admin', $tenantA->id);
        User::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
        User::factory()->count(4)->create(['tenant_id' => $tenantB->id]);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/staff?per_page=50');

        // userA + the 2 extra tenant-A staff created above = 3.
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_creating_a_member_stamps_it_with_the_acting_users_tenant(): void
    {
        $tenant = $this->otherTenant();
        $user = $this->userWithFullAccess(['Members'], 'Super Admin', $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Tenant-Stamped Member',
            'email' => 'stamped@example.test',
            'phone' => '+20 100 000 1234',
            'gender' => 'Male',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('members', ['email' => 'stamped@example.test', 'tenant_id' => $tenant->id]);
    }

    public function test_a_platform_admin_can_see_across_every_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        Member::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
        Member::factory()->count(3)->create(['tenant_id' => $tenantB->id]);

        $admin = User::factory()->platformAdmin()->create();
        // Platform admin bypasses the per-module RolePermission matrix too
        // (see CheckPermission — role 'Super Admin' already has full access
        // in the seeded matrix, so no extra RolePermission rows needed here
        // beyond the standard factory-created ones used across this suite).
        RolePermission::factory()->create([
            'role' => 'Super Admin', 'module' => 'Members',
            'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/members?per_page=50');

        $response->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_gym_settings_are_isolated_per_tenant(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();

        GymSetting::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Gym A Settings']);
        GymSetting::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Gym B Settings']);

        $userA = $this->userWithFullAccess(['Settings'], 'Super Admin', $tenantA->id);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/settings');

        $response->assertOk()->assertJsonPath('data.name', 'Gym A Settings');
    }
}
