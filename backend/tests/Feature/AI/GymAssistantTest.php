<?php

namespace Tests\Feature\AI;

use App\Models\AIUsageLog;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAI;
use Tests\TestCase;

class GymAssistantTest extends TestCase
{
    use InteractsWithAI, RefreshDatabase;

    public function test_a_staff_member_can_ask_the_assistant_a_question(): void
    {
        $fake = $this->bindFakeAIProvider('You have 0 active members right now.');
        $admin = $this->aiEnabledAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/ai/assistant', [
            'question' => 'How many active members do we have?',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.answer', 'You have 0 active members right now.')
            ->assertJsonPath('data.question', 'How many active members do we have?')
            ->assertJsonStructure(['data' => ['data' => ['activeMembers', 'membershipsExpiringThisWeek', 'thisMonthRevenue', 'popularClasses', 'equipmentNeedingMaintenance', 'lowAttendanceMembers']]]);

        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('ai_usage_logs', [
            'tenant_id' => $admin->tenant_id,
            'user_id' => $admin->id,
            'feature' => 'assistant',
            'status' => 'success',
        ]);
    }

    public function test_the_assistant_only_uses_the_authenticated_tenants_data(): void
    {
        $fake = $this->bindFakeAIProvider();
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();

        Member::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Tenant A Member', 'status' => 'Active']);
        Member::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Tenant B Secret Member', 'status' => 'Active']);

        $admin = $this->aiEnabledAdmin(null, tenant: $tenantA);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/ai/assistant', [
            'question' => 'How many active members do we have?',
        ]);

        $response->assertOk()->assertJsonPath('data.data.activeMembers', 1);

        $sentPrompt = json_encode($fake->calls[0]['messages']);
        $this->assertStringNotContainsString('Tenant B Secret Member', $sentPrompt);
    }

    public function test_a_role_without_ai_permission_cannot_ask_the_assistant(): void
    {
        $this->bindFakeAIProvider();
        $tenant = $this->tenantWithAIPlan();
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')
            ->postJson('/api/v1/ai/assistant', ['question' => 'How many active members do we have?'])
            ->assertStatus(403);
    }

    public function test_a_plan_with_ai_disabled_blocks_the_assistant(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin(aiLimit: 0);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/ai/assistant', ['question' => 'How many active members do we have?'])
            ->assertStatus(402);
    }

    public function test_a_tenant_that_reached_its_monthly_quota_is_blocked(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin(aiLimit: 1);

        AIUsageLog::factory()->create(['tenant_id' => $admin->tenant_id, 'feature' => 'assistant', 'status' => 'success']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/ai/assistant', ['question' => 'How many active members do we have?'])
            ->assertStatus(402);
    }

    public function test_a_provider_failure_returns_a_clean_error_and_is_recorded(): void
    {
        $this->bindFakeAIProvider(throw: new AIProviderException('boom'));
        $admin = $this->aiEnabledAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/ai/assistant', [
            'question' => 'How many active members do we have?',
        ]);

        $response->assertStatus(502);
        $this->assertDatabaseHas('ai_usage_logs', [
            'tenant_id' => $admin->tenant_id,
            'feature' => 'assistant',
            'status' => 'error',
        ]);
    }

    public function test_a_missing_question_is_rejected(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/ai/assistant', [])->assertStatus(422);
    }

    public function test_a_guest_cannot_access_the_assistant(): void
    {
        $this->postJson('/api/v1/ai/assistant', ['question' => 'Hi'])->assertStatus(401);
    }
}
