<?php

namespace Tests\Feature\Member;

use App\Models\AttendanceRecord;
use App\Models\GymClass;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

class MemberTenantIsolationTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_member_never_sees_another_tenants_attendance(): void
    {
        $memberA = $this->memberWithPassword(tenant: Tenant::default());
        $tenantB = Tenant::factory()->create();
        $memberB = $this->memberWithPassword(tenant: $tenantB);
        AttendanceRecord::factory()->create(['member_id' => $memberB->id, 'tenant_id' => $tenantB->id]);

        $response = $this->actingAs($memberA, 'sanctum')->getJson('/api/v1/member/attendance');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_member_never_sees_another_tenants_payments(): void
    {
        $memberA = $this->memberWithPassword(tenant: Tenant::default());
        $tenantB = Tenant::factory()->create();
        $memberB = $this->memberWithPassword(tenant: $tenantB);
        Payment::factory()->create(['member_id' => $memberB->id, 'tenant_id' => $tenantB->id]);

        $response = $this->actingAs($memberA, 'sanctum')->getJson('/api/v1/member/payments');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_member_never_sees_another_tenants_classes(): void
    {
        $memberA = $this->memberWithPassword(tenant: Tenant::default());
        $tenantB = Tenant::factory()->create();
        GymClass::factory()->create(['tenant_id' => $tenantB->id, 'status' => 'Scheduled']);

        $response = $this->actingAs($memberA, 'sanctum')->getJson('/api/v1/member/classes');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_member_never_sees_another_tenants_membership_via_a_guessed_id(): void
    {
        $memberA = $this->memberWithPassword(tenant: Tenant::default());
        $tenantB = Tenant::factory()->create();
        $memberB = $this->memberWithPassword(tenant: $tenantB);
        Membership::factory()->create(['member_id' => $memberB->id, 'tenant_id' => $tenantB->id]);

        // memberA has no membership of their own -> a clean 404, never memberB's row.
        $this->actingAs($memberA, 'sanctum')->getJson('/api/v1/member/membership')->assertStatus(404);
    }
}
