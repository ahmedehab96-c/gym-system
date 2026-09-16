<?php

namespace Tests\Feature\Auth;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_with_view_permission_can_access_staff_index(): void
    {
        RolePermission::factory()->create([
            'role' => 'Super Admin',
            'module' => 'Staff',
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);
        $user = User::factory()->create(['role' => 'Super Admin']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff');

        $response->assertOk();
    }

    public function test_role_without_view_permission_is_forbidden_from_staff_index(): void
    {
        RolePermission::factory()->create([
            'role' => 'Trainer',
            'module' => 'Staff',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Trainer']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/staff');

        $response->assertStatus(403);
    }

    public function test_role_without_create_permission_cannot_store_staff(): void
    {
        RolePermission::factory()->create([
            'role' => 'Receptionist',
            'module' => 'Staff',
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Receptionist']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/staff', []);

        $response->assertStatus(403);
    }

    public function test_role_with_create_permission_can_store_staff(): void
    {
        RolePermission::factory()->create([
            'role' => 'Admin',
            'module' => 'Staff',
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/staff', [
            'name' => 'New Staff Member',
            'email' => 'new.staff.member@example.com',
            'password' => 'password123',
            'role' => 'Receptionist',
        ]);

        $response->assertStatus(201);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/staff');

        $response->assertStatus(401);
    }
}
