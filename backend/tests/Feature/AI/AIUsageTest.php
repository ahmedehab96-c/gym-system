<?php

namespace Tests\Feature\AI;

use App\Models\AIUsageLog;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAI;
use Tests\TestCase;

class AIUsageTest extends TestCase
{
    use InteractsWithAI, RefreshDatabase;

    public function test_it_reports_usage_against_the_tenants_plan_limit(): void
    {
        $admin = $this->aiEnabledAdmin(aiLimit: 10);
        AIUsageLog::factory()->count(3)->create(['tenant_id' => $admin->tenant_id, 'status' => 'success']);
        AIUsageLog::factory()->create(['tenant_id' => $admin->tenant_id, 'status' => 'error']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ai/usage');

        $response->assertOk()
            ->assertJsonPath('data.used', 3)
            ->assertJsonPath('data.limit', 10)
            ->assertJsonPath('data.remaining', 7);
    }

    public function test_unlimited_plans_report_a_null_limit_and_remaining(): void
    {
        $admin = $this->aiEnabledAdmin(aiLimit: null);
        AIUsageLog::factory()->create(['tenant_id' => $admin->tenant_id, 'status' => 'success']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ai/usage');

        $response->assertOk()
            ->assertJsonPath('data.used', 1)
            ->assertJsonPath('data.limit', null)
            ->assertJsonPath('data.remaining', null);
    }

    public function test_usage_stats_never_include_another_tenants_requests(): void
    {
        $tenantA = Tenant::default();
        $tenantB = Tenant::factory()->create();
        $admin = $this->aiEnabledAdmin(aiLimit: 10, tenant: $tenantA);

        AIUsageLog::factory()->create(['tenant_id' => $tenantA->id, 'status' => 'success']);
        AIUsageLog::factory()->count(5)->create(['tenant_id' => $tenantB->id, 'status' => 'success']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ai/usage');

        $response->assertOk()->assertJsonPath('data.used', 1);
    }

    public function test_a_guest_cannot_view_usage_stats(): void
    {
        $this->getJson('/api/v1/ai/usage')->assertStatus(401);
    }
}
