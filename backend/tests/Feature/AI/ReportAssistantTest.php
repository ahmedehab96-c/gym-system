<?php

namespace Tests\Feature\AI;

use App\Models\User;
use App\Services\AI\ReportAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithAI;
use Tests\TestCase;

class ReportAssistantTest extends TestCase
{
    use InteractsWithAI, RefreshDatabase;

    #[DataProvider('reportTypeProvider')]
    public function test_it_summarizes_each_report_type(string $reportType): void
    {
        $fake = $this->bindFakeAIProvider("Here is your {$reportType} summary.");
        $admin = $this->aiEnabledAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/ai/reports/summarize', [
            'report_type' => $reportType,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reportType', $reportType)
            ->assertJsonPath('data.summary', "Here is your {$reportType} summary.")
            ->assertJsonStructure(['data' => ['data', 'generatedAt']]);

        $this->assertCount(1, $fake->calls);
        $this->assertDatabaseHas('ai_usage_logs', [
            'tenant_id' => $admin->tenant_id,
            'feature' => "report_summary:{$reportType}",
            'status' => 'success',
        ]);
    }

    public static function reportTypeProvider(): array
    {
        return collect(ReportAssistantService::REPORT_TYPES)->map(fn (string $t) => [$t])->all();
    }

    public function test_an_unknown_report_type_is_rejected(): void
    {
        $this->bindFakeAIProvider();
        $admin = $this->aiEnabledAdmin();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/ai/reports/summarize', ['report_type' => 'not-a-real-report'])
            ->assertStatus(422);
    }

    public function test_a_role_without_ai_permission_cannot_summarize_reports(): void
    {
        $this->bindFakeAIProvider();
        $tenant = $this->tenantWithAIPlan();
        $receptionist = User::factory()->create(['role' => 'Receptionist', 'tenant_id' => $tenant->id]);

        $this->actingAs($receptionist, 'sanctum')
            ->postJson('/api/v1/ai/reports/summarize', ['report_type' => 'revenue'])
            ->assertStatus(403);
    }
}
