<?php

namespace Tests\Feature\Console;

use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateMembershipStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_notifies_staff_when_a_membership_transitions_to_expiring_soon(): void
    {
        $staff = User::factory()->create(['status' => 'Active']);
        $member = Member::factory()->create();
        $membership = Membership::factory()->create([
            'member_id' => $member->id,
            'status' => 'Active',
            'expiry_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('memberships:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('memberships', ['id' => $membership->id, 'status' => 'Expiring Soon']);
        $this->assertDatabaseHas('notifications', ['type' => 'Membership Expiring', 'user_id' => $staff->id]);
    }

    public function test_it_notifies_staff_when_a_membership_transitions_to_expired(): void
    {
        $staff = User::factory()->create(['status' => 'Active']);
        $member = Member::factory()->create();
        $membership = Membership::factory()->create([
            'member_id' => $member->id,
            'status' => 'Active',
            'expiry_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $this->artisan('memberships:update-statuses')->assertSuccessful();

        $this->assertDatabaseHas('memberships', ['id' => $membership->id, 'status' => 'Expired']);
        $this->assertDatabaseHas('notifications', ['type' => 'Membership Expired', 'user_id' => $staff->id]);
    }

    public function test_it_does_not_notify_when_status_is_unchanged(): void
    {
        $staff = User::factory()->create(['status' => 'Active']);
        $member = Member::factory()->create();
        Membership::factory()->create([
            'member_id' => $member->id,
            'status' => 'Active',
            'expiry_date' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        $this->artisan('memberships:update-statuses')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['user_id' => $staff->id]);
    }
}
