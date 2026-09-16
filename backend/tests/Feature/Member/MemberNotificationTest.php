<?php

namespace Tests\Feature\Member;

use App\Models\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

class MemberNotificationTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_member_can_list_their_own_notifications(): void
    {
        $member = $this->memberWithPassword();
        AppNotification::create(['tenant_id' => $member->tenant_id, 'type' => 'New Member', 'title' => 'Welcome!', 'message' => 'Hi', 'member_id' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/notifications');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_member_can_mark_a_notification_read(): void
    {
        $member = $this->memberWithPassword();
        $notification = AppNotification::create(['tenant_id' => $member->tenant_id, 'type' => 'New Member', 'title' => 'Welcome!', 'message' => 'Hi', 'member_id' => $member->id, 'read' => false]);

        $response = $this->actingAs($member, 'sanctum')->patchJson("/api/v1/member/notifications/{$notification->id}/read");

        $response->assertOk()->assertJsonPath('data.read', true);
    }

    public function test_a_member_can_mark_all_notifications_read(): void
    {
        $member = $this->memberWithPassword();
        AppNotification::create(['tenant_id' => $member->tenant_id, 'type' => 'New Member', 'title' => 'A', 'message' => 'A', 'member_id' => $member->id, 'read' => false]);
        AppNotification::create(['tenant_id' => $member->tenant_id, 'type' => 'Payment Received', 'title' => 'B', 'message' => 'B', 'member_id' => $member->id, 'read' => false]);

        $this->actingAs($member, 'sanctum')->postJson('/api/v1/member/notifications/mark-all-read')->assertOk();

        $this->assertSame(0, AppNotification::where('member_id', $member->id)->where('read', false)->count());
    }

    public function test_a_member_cannot_mark_another_members_notification_read(): void
    {
        $member = $this->memberWithPassword();
        $other = $this->memberWithPassword(tenant: $member->tenant);
        $notification = AppNotification::create(['tenant_id' => $other->tenant_id, 'type' => 'New Member', 'title' => 'A', 'message' => 'A', 'member_id' => $other->id]);

        $this->actingAs($member, 'sanctum')->patchJson("/api/v1/member/notifications/{$notification->id}/read")->assertStatus(404);
    }

    public function test_a_staff_notification_never_appears_in_a_members_inbox(): void
    {
        $member = $this->memberWithPassword();
        AppNotification::create(['tenant_id' => $member->tenant_id, 'type' => 'New Member', 'title' => 'Staff notice', 'message' => 'x', 'user_id' => null]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/notifications');

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}
