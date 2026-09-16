<?php

namespace Tests\Feature\Equipment;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class MaintenanceRecordTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_maintenance_records_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        MaintenanceRecord::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_maintenance_records_by_type_or_technician(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        MaintenanceRecord::factory()->create(['type' => 'Belt Replacement', 'technician' => 'Findable Tech']);
        MaintenanceRecord::factory()->create(['type' => 'Deep Clean', 'technician' => 'Someone Else']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_maintenance_records_by_equipment_and_status(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create();
        $match = MaintenanceRecord::factory()->create(['equipment_id' => $equipment->id, 'status' => 'Completed']);
        MaintenanceRecord::factory()->create(['equipment_id' => $equipment->id, 'status' => 'Upcoming']);
        MaintenanceRecord::factory()->create(['status' => 'Completed']);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            "/api/v1/maintenance?equipment_id={$equipment->id}&status=Completed"
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_creates_a_maintenance_record(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/maintenance', [
            'equipment_id' => $equipment->id,
            'type' => 'Routine Service',
            'technician' => 'Ali Hassan',
            'date' => '2026-11-01',
            'cost' => 500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'Routine Service')
            ->assertJsonPath('data.equipmentId', $equipment->id)
            ->assertJsonPath('data.equipmentName', $equipment->name)
            ->assertJsonPath('data.status', 'Upcoming');
    }

    public function test_creating_a_maintenance_record_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/maintenance', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['equipment_id', 'type', 'date']);
    }

    public function test_completing_a_record_syncs_equipment_maintenance_dates_and_condition(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create(['condition' => 'Needs Maintenance', 'status' => 'Under Maintenance']);
        $record = MaintenanceRecord::factory()->create(['equipment_id' => $equipment->id, 'status' => 'In Progress']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/maintenance/{$record->id}", [
            'status' => 'Completed',
            'date' => '2026-11-01',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'Completed');

        $equipment->refresh();
        $this->assertSame('2026-11-01', $equipment->last_maintenance->toDateString());
        $this->assertSame('2027-01-30', $equipment->next_maintenance->toDateString());
        $this->assertSame('Good', $equipment->condition);
        $this->assertSame('In Use', $equipment->status);
    }

    public function test_starting_a_record_marks_equipment_under_maintenance(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create(['status' => 'In Use']);
        $record = MaintenanceRecord::factory()->create(['equipment_id' => $equipment->id, 'status' => 'Upcoming']);

        $this->actingAs($user, 'sanctum')->putJson("/api/v1/maintenance/{$record->id}", [
            'status' => 'In Progress',
        ])->assertOk();

        $this->assertSame('Under Maintenance', $equipment->refresh()->status);
    }

    public function test_it_lists_maintenance_history_for_equipment(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create();
        MaintenanceRecord::factory()->count(3)->create(['equipment_id' => $equipment->id]);
        MaintenanceRecord::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/equipment/{$equipment->id}/maintenance");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_returns_upcoming_maintenance(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        MaintenanceRecord::factory()->create(['status' => 'Upcoming', 'date' => Carbon::today()->addDays(5)->toDateString()]);
        MaintenanceRecord::factory()->create(['status' => 'Upcoming', 'date' => Carbon::today()->subDays(5)->toDateString()]);
        MaintenanceRecord::factory()->create(['status' => 'Completed', 'date' => Carbon::today()->addDays(5)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance/upcoming');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_returns_overdue_maintenance_including_stale_upcoming_records(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        MaintenanceRecord::factory()->create(['status' => 'Overdue', 'date' => Carbon::today()->subDays(10)->toDateString()]);
        MaintenanceRecord::factory()->create(['status' => 'Upcoming', 'date' => Carbon::today()->subDays(2)->toDateString()]);
        MaintenanceRecord::factory()->create(['status' => 'Upcoming', 'date' => Carbon::today()->addDays(2)->toDateString()]);
        MaintenanceRecord::factory()->create(['status' => 'Completed', 'date' => Carbon::today()->subDays(10)->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance/overdue');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_it_returns_maintenance_statistics(): void
    {
        $user = $this->userWithFullAccess(['Maintenance']);
        MaintenanceRecord::factory()->create(['status' => 'Upcoming', 'date' => Carbon::today()->addDay(), 'cost' => 100]);
        MaintenanceRecord::factory()->create(['status' => 'Overdue', 'date' => Carbon::today()->subDays(3), 'cost' => 200]);
        MaintenanceRecord::factory()->create(['status' => 'Completed', 'date' => Carbon::today()->subDays(10), 'cost' => 300]);
        MaintenanceRecord::factory()->create(['status' => 'In Progress', 'date' => Carbon::today(), 'cost' => 50]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance/stats');

        $response->assertOk()
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.upcoming', 1)
            ->assertJsonPath('data.overdue', 1)
            ->assertJsonPath('data.inProgress', 1)
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.totalCost', 650);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant',
            'module' => 'Maintenance',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/maintenance');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_maintenance(): void
    {
        $response = $this->getJson('/api/v1/maintenance');

        $response->assertStatus(401);
    }
}
