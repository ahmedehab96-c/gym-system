<?php

namespace Tests\Feature\Member;

use App\Models\GymClass;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMemberAuth;
use Tests\TestCase;

class MemberClassBookingTest extends TestCase
{
    use InteractsWithMemberAuth, RefreshDatabase;

    public function test_a_member_can_list_upcoming_classes(): void
    {
        $member = $this->memberWithPassword();
        GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'date' => now()->addDay()->toDateString()]);
        GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Cancelled', 'date' => now()->addDay()->toDateString()]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/classes');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_member_can_search_classes_by_name(): void
    {
        $member = $this->memberWithPassword();
        GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'name' => 'Sunrise Yoga']);
        GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'name' => 'HIIT Blast']);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/classes?search=yoga');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Sunrise Yoga');
    }

    public function test_a_member_can_book_a_class(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $class = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);

        $response = $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$class->id}/book");

        $response->assertOk();
        $this->assertDatabaseHas('class_bookings', ['class_id' => $class->id, 'member_id' => $member->id]);
    }

    public function test_a_member_cannot_book_the_same_class_twice(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $class = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$class->id}/book")->assertOk();
        $response = $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$class->id}/book");

        $response->assertStatus(422);
        $this->assertSame(1, $class->bookings()->count());
    }

    public function test_a_member_cannot_book_a_full_class(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $class = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 1]);
        $otherMember = $this->memberWithPassword(tenant: $member->tenant, attributes: ['status' => 'Active']);
        $class->bookings()->create(['member_id' => $otherMember->id, 'booked_at' => now()]);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$class->id}/book")->assertStatus(422);
    }

    public function test_an_inactive_member_cannot_book_a_class(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Suspended']);
        $class = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$class->id}/book")->assertStatus(422);
    }

    public function test_a_member_can_cancel_their_booking(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $class = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);
        $class->bookings()->create(['member_id' => $member->id, 'booked_at' => now()]);

        $this->actingAs($member, 'sanctum')->deleteJson("/api/v1/member/classes/{$class->id}/book")->assertOk();

        $this->assertDatabaseMissing('class_bookings', ['class_id' => $class->id, 'member_id' => $member->id]);
    }

    public function test_a_member_can_list_their_own_bookings(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $booked = GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);
        GymClass::factory()->create(['tenant_id' => $member->tenant_id, 'status' => 'Scheduled', 'capacity' => 10]);
        $booked->bookings()->create(['member_id' => $member->id, 'booked_at' => now()]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/v1/member/classes/my-bookings');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $booked->id);
    }

    public function test_booking_a_cross_tenant_class_returns_not_found(): void
    {
        $member = $this->memberWithPassword(attributes: ['status' => 'Active']);
        $otherTenantClass = GymClass::factory()->create(['tenant_id' => Tenant::factory(), 'status' => 'Scheduled']);

        $this->actingAs($member, 'sanctum')->postJson("/api/v1/member/classes/{$otherTenantClass->id}/book")->assertStatus(404);
    }
}
