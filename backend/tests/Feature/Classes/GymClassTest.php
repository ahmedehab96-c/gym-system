<?php

namespace Tests\Feature\Classes;

use App\Models\ClassBooking;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class GymClassTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_classes_with_pagination(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        GymClass::factory()->count(20)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/classes?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_searches_classes_by_name(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        GymClass::factory()->create(['name' => 'Findable Class']);
        GymClass::factory()->create(['name' => 'Other Class']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/classes?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_it_filters_classes_by_trainer_day_and_status(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        $match = GymClass::factory()->create(['trainer_id' => $trainer->id, 'day' => 'Monday', 'status' => 'Scheduled']);
        GymClass::factory()->create(['day' => 'Tuesday', 'status' => 'Scheduled']);
        GymClass::factory()->create(['trainer_id' => $trainer->id, 'day' => 'Monday', 'status' => 'Cancelled']);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            "/api/v1/classes?trainer_id={$trainer->id}&day=Monday&status=Scheduled"
        );

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $match->id);
    }

    public function test_it_creates_a_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', [
            'name' => 'New Class',
            'category' => 'Cardio',
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'capacity' => 20,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Class')
            ->assertJsonPath('data.trainerId', $trainer->id)
            ->assertJsonPath('data.status', 'Scheduled')
            ->assertJsonPath('data.booked', 0)
            ->assertJsonPath('data.duration', '1h');
    }

    public function test_creating_a_class_requires_valid_data(): void
    {
        $user = $this->userWithFullAccess(['Classes']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'trainer_id', 'day', 'start_time', 'end_time', 'capacity']);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', [
            'name' => 'Bad Time Class',
            'trainer_id' => $trainer->id,
            'day' => 'Monday',
            'start_time' => '10:00',
            'end_time' => '09:00',
            'capacity' => 10,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('end_time');
    }

    public function test_it_rejects_creating_an_overlapping_class_for_the_same_trainer(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create([
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', [
            'name' => 'Overlapping Class',
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:30',
            'end_time' => '10:30',
            'capacity' => 10,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_allows_back_to_back_classes_for_the_same_trainer(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create([
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', [
            'name' => 'Back To Back Class',
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'capacity' => 10,
        ]);

        $response->assertCreated();
    }

    public function test_it_ignores_cancelled_classes_when_checking_for_conflicts(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create([
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Cancelled',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/classes', [
            'name' => 'New Class',
            'trainer_id' => $trainer->id,
            'date' => '2026-10-05',
            'day' => 'Monday',
            'start_time' => '09:30',
            'end_time' => '10:30',
            'capacity' => 10,
        ]);

        $response->assertCreated();
    }

    public function test_it_shows_a_class_with_booked_members(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create();
        $member = Member::factory()->create();
        ClassBooking::factory()->create(['class_id' => $class->id, 'member_id' => $member->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/classes/{$class->id}");

        $response->assertOk()->assertJsonPath('data.booked', 1)->assertJsonCount(1, 'data.bookedMembers');
    }

    public function test_it_updates_a_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/classes/{$class->id}", [
            'name' => 'New Name',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_updating_a_class_rejects_a_conflicting_reschedule(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $trainer = Trainer::factory()->create();
        GymClass::factory()->create([
            'trainer_id' => $trainer->id, 'date' => '2026-10-05', 'day' => 'Monday',
            'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'Scheduled',
        ]);
        $moving = GymClass::factory()->create([
            'trainer_id' => $trainer->id, 'date' => '2026-10-05', 'day' => 'Monday',
            'start_time' => '12:00', 'end_time' => '13:00', 'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/classes/{$moving->id}", [
            'start_time' => '09:30',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_deletes_a_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('gym_classes', ['id' => $class->id]);
    }

    public function test_it_books_a_member_into_a_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 10, 'status' => 'Scheduled']);
        $member = Member::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $member->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.booked', 1);
        $this->assertDatabaseHas('class_bookings', ['class_id' => $class->id, 'member_id' => $member->id]);
    }

    public function test_it_prevents_booking_an_inactive_member(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 10, 'status' => 'Scheduled']);
        $member = Member::factory()->create(['status' => 'Suspended']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('class_bookings', ['class_id' => $class->id, 'member_id' => $member->id]);
    }

    public function test_it_prevents_duplicate_bookings(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 10, 'status' => 'Scheduled']);
        $member = Member::factory()->create(['status' => 'Active']);
        ClassBooking::factory()->create(['class_id' => $class->id, 'member_id' => $member->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, ClassBooking::where('class_id', $class->id)->count());
    }

    public function test_it_prevents_booking_a_full_class_and_marks_it_full(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 1, 'status' => 'Scheduled']);
        $firstMember = Member::factory()->create(['status' => 'Active']);
        $secondMember = Member::factory()->create(['status' => 'Active']);

        $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $firstMember->id,
        ])->assertCreated()->assertJsonPath('data.status', 'Full');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $secondMember->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_prevents_booking_a_cancelled_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['status' => 'Cancelled']);
        $member = Member::factory()->create(['status' => 'Active']);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", [
            'member_id' => $member->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_it_cancels_a_booking_and_reopens_a_full_class(): void
    {
        $user = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 1, 'status' => 'Full']);
        $member = Member::factory()->create();
        ClassBooking::factory()->create(['class_id' => $class->id, 'member_id' => $member->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/classes/{$class->id}/book/{$member->id}");

        $response->assertOk()->assertJsonPath('data.booked', 0)->assertJsonPath('data.status', 'Scheduled');
        $this->assertDatabaseMissing('class_bookings', ['class_id' => $class->id, 'member_id' => $member->id]);
    }

    public function test_a_role_without_permission_is_forbidden(): void
    {
        RolePermission::factory()->create([
            'role' => 'Accountant',
            'module' => 'Classes',
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $user = User::factory()->create(['role' => 'Accountant']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/classes');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_classes(): void
    {
        $response = $this->getJson('/api/v1/classes');

        $response->assertStatus(401);
    }
}
