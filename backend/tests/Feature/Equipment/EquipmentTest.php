<?php

namespace Tests\Feature\Equipment;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_equipment_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        Equipment::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/equipment?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_equipment_by_name_brand_or_model(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        Equipment::factory()->create(['name' => 'Findable Treadmill', 'brand' => 'Technogym']);
        Equipment::factory()->create(['name' => 'Other Machine']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/equipment?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_equipment_by_category_status_and_condition(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        $match = Equipment::factory()->create(['category' => 'Cardio', 'status' => 'In Use', 'condition' => 'Excellent']);
        Equipment::factory()->create(['category' => 'Strength', 'status' => 'In Use', 'condition' => 'Excellent']);
        Equipment::factory()->create(['category' => 'Cardio', 'status' => 'Retired', 'condition' => 'Excellent']);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            '/api/v1/equipment?category=Cardio&status=In Use&condition=Excellent'
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_sorts_equipment_by_next_maintenance(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        Equipment::factory()->create(['name' => 'Later', 'next_maintenance' => '2027-01-01']);
        Equipment::factory()->create(['name' => 'Sooner', 'next_maintenance' => '2026-01-01']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/equipment?sort_by=nextMaintenance&sort_dir=asc');

        $response->assertOk()->assertJsonPath('data.0.name', 'Sooner');
    }

    public function test_it_creates_equipment(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/equipment', [
            'name' => 'New Treadmill',
            'category' => 'Cardio',
            'brand' => 'Technogym',
            'model' => 'RUN-500',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Treadmill')
            ->assertJsonPath('data.condition', 'Excellent')
            ->assertJsonPath('data.status', 'In Use');
    }

    public function test_creating_equipment_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/equipment', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'category']);
    }

    public function test_it_shows_equipment_with_maintenance_history(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        $equipment = Equipment::factory()->create();
        MaintenanceRecord::factory()->count(2)->create(['equipment_id' => $equipment->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/equipment/{$equipment->id}");

        $response->assertOk()->assertJsonCount(2, 'data.maintenanceHistory');
    }

    public function test_it_updates_equipment(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        $equipment = Equipment::factory()->create(['status' => 'In Use']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/equipment/{$equipment->id}", [
            'status' => 'Retired',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'Retired');
    }

    public function test_it_deletes_equipment(): void
    {
        $user = $this->userWithFullAccess(['Equipment']);
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/equipment/{$equipment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('equipment', ['id' => $equipment->id]);
    }

    public function test_it_uploads_an_equipment_image_to_storage(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Equipment']);
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/equipment/{$equipment->id}/image", [
            'image' => UploadedFile::fake()->image('equipment.jpg'),
        ]);

        $response->assertOk();
        $this->assertStringContainsString('/storage/equipment/', $response->json('data.image'));
        $this->assertCount(1, Storage::disk('public')->files('equipment'));
    }

    public function test_uploading_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->userWithFullAccess(['Equipment']);
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/equipment/{$equipment->id}/image", [
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant',
            'module' => 'Equipment',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/equipment');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_equipment(): void
    {
        $response = $this->getJson('/api/v1/equipment');

        $response->assertStatus(401);
    }
}
