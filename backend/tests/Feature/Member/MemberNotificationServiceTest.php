<?php

namespace Tests\Feature\Member;

use App\Models\Membership;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

/** Verifies NotificationService's additive member_id fan-out (Phase 25), alongside its unchanged staff fan-out. */
class MemberNotificationServiceTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_new_member_receives_a_welcome_notification(): void
    {
        $member = $this->memberWithPassword();

        app(NotificationService::class)->newMember($member);

        $this->assertDatabaseHas('notifications', ['member_id' => $member->id, 'type' => 'New Member']);
    }

    public function test_a_member_is_notified_when_their_membership_is_expiring(): void
    {
        $member = $this->memberWithPassword();
        $membership = Membership::factory()->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id]);

        app(NotificationService::class)->membershipExpiring($membership);

        $this->assertDatabaseHas('notifications', ['member_id' => $member->id, 'type' => 'Membership Expiring']);
    }

    public function test_a_member_is_notified_when_a_payment_is_received(): void
    {
        $member = $this->memberWithPassword();
        $payment = Payment::factory()->create(['member_id' => $member->id, 'tenant_id' => $member->tenant_id]);

        app(NotificationService::class)->paymentReceived($payment);

        $this->assertDatabaseHas('notifications', ['member_id' => $member->id, 'type' => 'Payment Received']);
    }
}
