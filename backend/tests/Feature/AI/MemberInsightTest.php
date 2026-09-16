<?php

namespace Tests\Feature\AI;

use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAI;
use Tests\TestCase;

class MemberInsightTest extends TestCase
{
    use InteractsWithAI, RefreshDatabase;

    public function test_it_generates_engagement_suggestions_for_a_member(): void
    {
        $fake = $this->bindFakeAIProvider('- Call them about renewing.\n- Invite them to Saturday yoga.');
        $tenant = Tenant::default();
        $admin = $this->aiEnabledAdmin(tenant: $tenant);
        $member = Member::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Jane Doe', 'attendance_rate' => 12]);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/ai/members/{$member->id}/insights");

        $response->assertOk()
            ->assertJsonPath('data.memberId', (string) $member->id)
            ->assertJsonPath('data.data.name', 'Jane Doe')
            ->assertJsonPath('data.data.attendanceRate', 12);

        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('ai_usage_logs', ['tenant_id' => $tenant->id, 'feature' => 'member_insight', 'status' => 'success']);
    }

    public function test_the_prompt_contains_no_medical_advice_instruction(): void
    {
        $fake = $this->bindFakeAIProvider();
        $tenant = Tenant::default();
        $admin = $this->aiEnabledAdmin(tenant: $tenant);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($admin, 'sanctum')->getJson("/api/v1/ai/members/{$member->id}/insights")->assertOk();

        $systemMessage = collect($fake->calls[0]['messages'])->firstWhere('role', 'system');
        $this->assertStringContainsString('medical', strtolower($systemMessage['content']));
    }

    public function test_a_member_from_another_tenant_is_not_found(): void
    {
        $this->bindFakeAIProvider();
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $admin = $this->aiEnabledAdmin(tenant: $tenantA);
        $otherMember = Member::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/ai/members/{$otherMember->id}/insights")
            ->assertStatus(404);
    }

    public function test_a_role_without_ai_permission_cannot_view_member_insights(): void
    {
        $this->bindFakeAIProvider();
        $tenant = $this->tenantWithAIPlan();
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);
        $member = Member::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')->getJson("/api/v1/ai/members/{$member->id}/insights")->assertStatus(403);
    }
}
