<?php

namespace Tests\Feature\Trainer;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTrainerAuth;
use Tests\TestCase;

class TrainerAuthorizationTest extends TestCase
{
    use InteractsWithTrainerAuth, RefreshDatabase;

    public function test_a_trainer_can_access_trainer_routes(): void
    {
        [$user] = $this->trainerAccount();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/dashboard')->assertOk();
    }

    public function test_a_non_trainer_staff_role_is_forbidden_from_trainer_routes(): void
    {
        $tenant = Tenant::default();
        $receptionist = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'Receptionist']);

        $this->actingAs($receptionist, 'sanctum')->getJson('/api/v1/trainer/dashboard')->assertStatus(403);
    }

    public function test_an_admin_is_also_forbidden_from_trainer_only_routes(): void
    {
        $tenant = Tenant::default();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'Admin']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/trainer/profile')->assertStatus(403);
    }

    public function test_a_guest_cannot_access_trainer_routes(): void
    {
        $this->getJson('/api/v1/trainer/dashboard')->assertStatus(401);
    }

    public function test_a_trainer_role_user_with_no_linked_roster_row_gets_a_clean_404(): void
    {
        $tenant = Tenant::default();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'Trainer']);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/trainer/dashboard')->assertStatus(404);
    }

    public function test_the_trainer_login_response_includes_the_permission_matrix_the_app_uses_to_gate_actions(): void
    {
        [$user] = $this->trainerAccount();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertOk();
        $permissions = collect($response->json('data.permissions'));
        $attendance = $permissions->firstWhere('module', 'Attendance');

        $this->assertNotNull($attendance);
        $this->assertTrue($attendance['canView']);
        $this->assertFalse($attendance['canEdit']);
    }
}
