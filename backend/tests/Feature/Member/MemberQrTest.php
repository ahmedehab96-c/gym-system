<?php

namespace Tests\Feature\Member;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

class MemberQrTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_it_issues_a_qr_token_on_first_access(): void
    {
        $member = $this->memberWithPassword();
        $this->assertNull($member->qr_token);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr');

        $response->assertOk();
        $this->assertNotNull($response->json('data.token'));
        $this->assertNotNull($response->json('data.expiresAt'));
        $this->assertNotNull($member->fresh()->qr_token_hash);
    }

    public function test_it_returns_the_same_token_on_a_later_fetch(): void
    {
        $member = $this->memberWithPassword();
        $first = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr')->json('data.token');

        $second = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr')->json('data.token');

        $this->assertSame($first, $second);
    }

    public function test_regenerate_issues_a_different_token_and_invalidates_the_old_one(): void
    {
        $member = $this->memberWithPassword();
        $original = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr')->json('data.token');

        $response = $this->actingAs($member, 'sanctum')->postJson('/api/v1/member/qr/regenerate');

        $response->assertOk();
        $newToken = $response->json('data.token');
        $this->assertNotSame($original, $newToken);
        $this->assertNull(Member::findByQrTokenHash($original));
        $this->assertNotNull(Member::findByQrTokenHash($newToken));
    }

    public function test_an_expired_token_is_transparently_replaced_on_next_fetch(): void
    {
        $member = $this->memberWithPassword();
        $old = $member->issueQrToken();
        $member->forceFill(['qr_token_expires_at' => now()->subDay()])->save();

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr');

        $response->assertOk();
        $this->assertNotSame($old, $response->json('data.token'));
    }

    public function test_qr_response_includes_member_and_membership_status(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Suspended']);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr');

        $response->assertOk()->assertJsonPath('data.memberStatus', 'Suspended');
    }

    public function test_qr_response_reports_expired_even_when_the_status_column_is_stale(): void
    {
        // The `status` column says Active but expiry_date has passed —
        // the daily UpdateMembershipStatuses job hasn't caught up yet.
        // The My QR screen must show what a check-in attempt would
        // actually see (Expired), never a falsely-reassuring Active.
        $member = $this->memberWithPassword(attributes: ['status' => 'Active', 'expiry_date' => now()->subDay()->toDateString()]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/qr');

        $response->assertOk()->assertJsonPath('data.memberStatus', 'Expired');
    }

    public function test_guests_cannot_access_a_members_qr(): void
    {
        $response = $this->getJson('/api/v1/member/qr');

        $response->assertStatus(401);
    }

    public function test_a_regular_staff_token_cannot_access_the_member_qr_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/member/qr');

        $response->assertStatus(403);
    }
}
