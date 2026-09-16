<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Services\AI\InsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithAI;
use Tests\TestCase;

class InsightTest extends TestCase
{
    use InteractsWithAI, RefreshDatabase;

    #[DataProvider('domainProvider')]
    public function test_it_generates_a_structured_insight_for_each_domain(string $domain): void
    {
        $fake = $this->bindFakeAIProvider("Here is your {$domain} insight.");
        $admin = $this->aiEnabledAdmin();

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/ai/insights/{$domain}");

        $response->assertOk()
            ->assertJsonPath('data.domain', $domain)
            ->assertJsonPath('data.summary', "Here is your {$domain} insight.")
            ->assertJsonStructure(['data' => ['metrics', 'generatedAt']]);

        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('ai_usage_logs', [
            'tenant_id' => $admin->tenant_id,
            'feature' => "insight:{$domain}",
            'status' => 'success',
        ]);
    }

    public static function domainProvider(): array
    {
        return collect(InsightService::DOMAINS)->map(fn (string $d) => [$d])->all();
    }

    public function test_an_unknown_domain_is_rejected(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin();

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ai/insights/not-a-real-domain')->assertStatus(422);
    }

    public function test_a_role_without_ai_permission_cannot_view_insights(): void
    {
        $this->bindFakeAIProvider();
        $tenant = $this->tenantWithAIPlan();
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')->getJson('/api/v1/ai/insights/members')->assertStatus(403);
    }

    public function test_a_tenant_at_its_quota_is_blocked_from_generating_insights(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin(aiLimit: 0);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/ai/insights/members')->assertStatus(402);
    }

    public function test_a_guest_cannot_view_insights(): void
    {
        $this->getJson('/api/v1/ai/insights/members')->assertStatus(401);
    }
}
