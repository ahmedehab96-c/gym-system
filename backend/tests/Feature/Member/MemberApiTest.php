<?php

namespace Tests\Feature\Member;

use App\Models\AttendanceRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Trainer;
use App\Models\TrainingProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_member_can_view_their_current_membership(): void
    {
        $member = $this->memberWithPassword();
        Membership::factory()->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id, 'start_date' => now()->subMonth(), 'status' => 'Active']);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/membership')
            ->assertOk()
            ->assertJsonPath('data.memberId', $member->id);
    }

    public function test_a_member_with_no_membership_gets_a_clean_404(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/membership')->assertStatus(404);
    }

    public function test_a_member_can_view_membership_history(): void
    {
        $member = $this->memberWithPassword();
        Membership::factory()->count(3)->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id]);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/membership/history')
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_member_can_view_their_attendance_history_and_summary(): void
    {
        $member = $this->memberWithPassword();
        AttendanceRecord::factory()->count(2)->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id, 'date' => now()]);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/attendance')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/attendance/summary')
            ->assertOk()->assertJsonPath('data.visitsThisMonth', 2);
    }

    public function test_a_member_can_list_trainers_and_view_one(): void
    {
        $member = $this->memberWithPassword();
        $trainer = Trainer::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Active']);
        Trainer::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Inactive']);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/trainers')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($member, 'sanctum')->getJson("/api/v1/member/trainers/{$trainer->id}")
            ->assertOk()->assertJsonPath('data.id', $trainer->id);
    }

    public function test_a_member_can_list_programs_and_see_their_own_enrollment(): void
    {
        $member = $this->memberWithPassword();
        $program = TrainingProgram::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Active']);
        $program->members()->attach($member->id, ['enrolled_at' => now()]);
        TrainingProgram::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Active']);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/programs')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/programs/mine')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $program->id);
    }

    public function test_a_member_can_view_their_own_payments(): void
    {
        $member = $this->memberWithPassword();
        Payment::factory()->count(2)->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id]);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/payments')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_member_can_view_their_own_invoices(): void
    {
        $member = $this->memberWithPassword();
        $invoice = Invoice::factory()->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id, 'total' => 500]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'amount' => 500]);

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/invoices')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($member, 'sanctum')->getJson("/api/v1/member/invoices/{$invoice->id}")
            ->assertOk()->assertJsonPath('data.total', 500);
    }

    public function test_a_member_cannot_view_another_members_invoice(): void
    {
        $member = $this->memberWithPassword();
        $other = $this->memberWithPassword();
        $invoice = Invoice::factory()->create(['member_id' => $other->id, 'tenant_id' => $other->tenant_id]);

        $this->actingAs($member, 'sanctum')->getJson("/api/v1/member/invoices/{$invoice->id}")->assertStatus(404);
    }

    public function test_a_member_can_view_and_update_their_profile(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/profile')->assertOk();

        $response = $this->actingAs($member, 'sanctum')->putJson('/api/v1/member/profile', [
            'name' => 'Updated Name',
            'phone' => '+1 555 000 1111',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_a_member_cannot_set_their_own_plan_or_status_via_profile_update(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $originalStatus = $member->status;

        $this->actingAs($member, 'sanctum')->putJson('/api/v1/member/profile', [
            'status' => 'Inactive',
            'plan_id' => 999,
            'balance_due' => 0,
        ])->assertOk();

        $this->assertSame($originalStatus, $member->fresh()->status);
    }

    public function test_a_member_can_view_the_dashboard(): void
    {
        $member = $this->memberWithPassword();

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/dashboard');

        $response->assertOk()->assertJsonStructure([
            'data' => ['member', 'attendanceThisMonth', 'upcomingClasses', 'recentPayments', 'unreadNotifications'],
        ]);
    }

    public function test_a_member_can_register_a_device_token(): void
    {
        $member = $this->memberWithPassword();

        $this->actingAs($member, 'sanctum')->postJson('/api/v1/member/device-tokens', [
            'token' => 'flutter-device-token-1', 'platform' => 'android',
        ])->assertCreated();

        $this->assertDatabaseHas('device_tokens', ['member_id' => $member->id, 'user_id' => null, 'token' => 'flutter-device-token-1']);
    }

    public function test_guest_cannot_access_any_protected_member_route(): void
    {
        $this->getJson('/api/v1/member/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/member/membership')->assertStatus(401);
        $this->getJson('/api/v1/member/attendance')->assertStatus(401);
        $this->getJson('/api/v1/member/payments')->assertStatus(401);
    }
}
