<?php

namespace Tests\Feature\Notifications;

use App\Models\AppNotification;
use App\Models\Equipment;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_a_users_own_and_global_notifications_only(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $otherUser = User::factory()->create(['status' => 'Active']);

        AppNotification::factory()->create(['user_id' => $user->id, 'title' => 'Mine']);
        AppNotification::factory()->create(['user_id' => null, 'title' => 'Global']);
        AppNotification::factory()->create(['user_id' => $otherUser->id, 'title' => 'Not mine']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk()->assertJsonCount(2, 'data');
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Mine'));
        $this->assertTrue($titles->contains('Global'));
        $this->assertFalse($titles->contains('Not mine'));
    }

    public function test_it_paginates_notifications(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        AppNotification::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.perPage', 5);
    }

    public function test_it_filters_by_read_status_and_type(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        AppNotification::factory()->create(['user_id' => $user->id, 'read' => true, 'type' => 'Payment Received']);
        AppNotification::factory()->create(['user_id' => $user->id, 'read' => false, 'type' => 'New Member']);

        $unread = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?read=0');
        $unread->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'New Member');

        $byType = $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications?type=Payment Received');
        $byType->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.type', 'Payment Received');
    }

    public function test_it_marks_a_notification_as_read(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $notification = AppNotification::factory()->create(['user_id' => $user->id, 'read' => false]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/notifications/{$notification->id}", ['read' => true]);

        $response->assertOk()->assertJsonPath('data.read', true);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'read' => true]);
    }

    public function test_it_marks_all_of_a_users_notifications_as_read(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $other = User::factory()->create(['status' => 'Active']);

        AppNotification::factory()->create(['user_id' => $user->id, 'read' => false]);
        AppNotification::factory()->create(['user_id' => null, 'read' => false]);
        $othersUnread = AppNotification::factory()->create(['user_id' => $other->id, 'read' => false]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/notifications/mark-all-read');

        $response->assertOk();
        $this->assertSame(0, AppNotification::where(function ($q) use ($user) {
            $q->whereNull('user_id')->orWhere('user_id', $user->id);
        })->where('read', false)->count());
        $this->assertDatabaseHas('notifications', ['id' => $othersUnread->id, 'read' => false]);
    }

    public function test_it_deletes_a_notification(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $notification = AppNotification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/notifications/{$notification->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_a_user_cannot_modify_another_users_targeted_notification(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $other = User::factory()->create(['status' => 'Active']);
        $notification = AppNotification::factory()->create(['user_id' => $other->id]);

        $update = $this->actingAs($user, 'sanctum')->putJson("/api/v1/notifications/{$notification->id}", ['read' => true]);
        $update->assertStatus(403);

        $delete = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/notifications/{$notification->id}");
        $delete->assertStatus(403);
    }

    public function test_guest_cannot_access_notification_endpoints(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_creating_a_member_generates_a_new_member_notification(): void
    {
        $staff = $this->userWithFullAccess(['Members']);
        $plan = MembershipPlan::factory()->create();

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Fresh Member',
            'gender' => 'Male',
            'phone' => '+20 100 555 0000',
            'email' => 'fresh.member@example.com',
            'plan_id' => $plan->id,
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'type' => 'New Member',
            'user_id' => $staff->id,
        ]);
    }

    public function test_creating_a_paid_payment_generates_a_payment_received_notification(): void
    {
        $staff = $this->userWithFullAccess(['Payments']);
        $member = Member::factory()->create();

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/payments', [
            'member_id' => $member->id,
            'amount' => 500,
            'status' => 'Paid',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Payment Received',
            'user_id' => $staff->id,
        ]);
    }

    public function test_a_payment_status_change_generates_the_matching_notification(): void
    {
        $staff = $this->userWithFullAccess(['Payments']);
        $payment = Payment::factory()->create(['status' => 'Pending']);

        $this->actingAs($staff, 'sanctum')->putJson("/api/v1/payments/{$payment->id}", ['status' => 'Failed'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Payment Failed',
            'user_id' => $staff->id,
        ]);
    }

    public function test_cancelling_a_class_generates_a_class_cancellation_notification(): void
    {
        $staff = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['status' => 'Scheduled']);

        $this->actingAs($staff, 'sanctum')->putJson("/api/v1/classes/{$class->id}", ['status' => 'Cancelled'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Class Cancellation',
            'user_id' => $staff->id,
        ]);
    }

    public function test_booking_a_class_generates_a_class_reminder_notification(): void
    {
        $staff = $this->userWithFullAccess(['Classes']);
        $class = GymClass::factory()->create(['capacity' => 10, 'status' => 'Scheduled']);
        $member = Member::factory()->create(['status' => 'Active']);

        $this->actingAs($staff, 'sanctum')->postJson("/api/v1/classes/{$class->id}/book", ['member_id' => $member->id])
            ->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Class Reminder',
            'user_id' => $staff->id,
        ]);
    }

    public function test_scheduling_overdue_maintenance_generates_an_overdue_notification(): void
    {
        $staff = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create();

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/maintenance', [
            'equipment_id' => $equipment->id,
            'type' => 'Belt Replacement',
            'technician' => 'Sam',
            'date' => Carbon::today()->subDays(2)->toDateString(),
            'status' => 'Upcoming',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Maintenance Overdue',
            'user_id' => $staff->id,
        ]);
    }

    public function test_scheduling_upcoming_maintenance_generates_a_due_notification(): void
    {
        $staff = $this->userWithFullAccess(['Maintenance']);
        $equipment = Equipment::factory()->create();

        $this->actingAs($staff, 'sanctum')->postJson('/api/v1/maintenance', [
            'equipment_id' => $equipment->id,
            'type' => 'Deep Clean',
            'technician' => 'Sam',
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'status' => 'Upcoming',
        ])->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'type' => 'Maintenance Due',
            'user_id' => $staff->id,
        ]);
    }
}
