<?php

namespace Tests\Feature\Members;

use App\Models\Member;
use App\Models\MemberNote;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_members_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        Member::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/members?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_members_by_name_email_or_member_id(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        Member::factory()->create(['name' => 'Ziad Karim', 'email' => 'ziad@example.com', 'member_id' => 'GYM-1111']);
        Member::factory()->create(['name' => 'Somebody Else', 'email' => 'else@example.com', 'member_id' => 'GYM-2222']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/members?search=Ziad');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Ziad Karim');
    }

    public function test_it_filters_members_by_status(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        Member::factory()->create(['status' => 'Active']);
        Member::factory()->create(['status' => 'Suspended']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/members?status=Suspended');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Suspended');
    }

    public function test_it_sorts_members_by_attendance_rate(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        Member::factory()->create(['name' => 'Low', 'attendance_rate' => 10]);
        Member::factory()->create(['name' => 'High', 'attendance_rate' => 90]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/members?sort_by=attendanceRate&sort_dir=desc');

        $response->assertOk()->assertJsonPath('data.0.name', 'High');
    }

    public function test_it_creates_a_member_with_an_initial_membership(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $plan = MembershipPlan::factory()->create(['duration_days' => 30, 'price' => 600]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'New Member',
            'gender' => 'Male',
            'phone' => '+20 100 000 0000',
            'email' => 'new.member@example.com',
            'plan_id' => $plan->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Member')
            ->assertJsonPath('data.planId', $plan->id)
            ->assertJsonPath('data.status', 'Active');

        $member = Member::where('email', 'new.member@example.com')->firstOrFail();
        $this->assertNotNull($member->member_id);
        $this->assertDatabaseHas('memberships', ['member_id' => $member->id, 'plan_id' => $plan->id]);
    }

    public function test_creating_a_member_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Members']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/members', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'gender', 'phone', 'email']);
    }

    public function test_it_shows_a_member_with_notes_and_plan(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();
        MemberNote::factory()->create(['member_id' => $member->id, 'text' => 'Great progress']);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/members/{$member->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.notes.0.text', 'Great progress');
    }

    public function test_it_updates_a_member(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/members/{$member->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_it_updates_member_status(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/members/{$member->id}/status", [
            'status' => 'Suspended',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'Suspended');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Suspended']);
    }

    public function test_updating_member_status_requires_a_valid_status(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/members/{$member->id}/status", [
            'status' => 'NotAStatus',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_deletes_a_member(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/members/{$member->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_it_prevents_deleting_a_member_with_payment_records(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();
        Payment::factory()->create(['member_id' => $member->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/members/{$member->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Members',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/members');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_members(): void
    {
        $response = $this->getJson('/api/v1/members');

        $response->assertStatus(401);
    }

    public function test_it_adds_a_note_to_a_member(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/members/{$member->id}/notes", [
            'text' => 'Called about renewal',
        ]);

        $response->assertCreated()->assertJsonPath('data.notes.0.text', 'Called about renewal');
        $this->assertDatabaseHas('member_notes', ['member_id' => $member->id, 'text' => 'Called about renewal', 'author' => $user->name]);
    }

    public function test_note_text_is_required(): void
    {
        $user = $this->userWithFullAccess(['Members']);
        $member = Member::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/members/{$member->id}/notes", []);

        $response->assertStatus(422)->assertJsonValidationErrors('text');
    }
}
