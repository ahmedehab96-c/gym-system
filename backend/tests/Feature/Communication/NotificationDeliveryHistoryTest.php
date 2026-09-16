<?php

namespace Tests\Feature\Communication;

use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Support\NotificationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class NotificationDeliveryHistoryTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_it_lists_deliveries_for_the_callers_tenant(): void
    {
        $tenant = Tenant::default();
        NotificationDelivery::create([
            'tenant_id' => $tenant->id, 'type' => NotificationType::NEW_MEMBER, 'channel' => 'email',
            'recipient' => 'someone@example.com', 'status' => 'Sent',
        ]);
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/communication/deliveries');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertStringContainsString('***@', $response->json('data.0.recipient'));
    }

    public function test_it_filters_by_status(): void
    {
        $tenant = Tenant::default();
        NotificationDelivery::create(['tenant_id' => $tenant->id, 'type' => NotificationType::NEW_MEMBER, 'channel' => 'email', 'recipient' => 'a@example.com', 'status' => 'Sent']);
        NotificationDelivery::create(['tenant_id' => $tenant->id, 'type' => NotificationType::NEW_MEMBER, 'channel' => 'email', 'recipient' => 'b@example.com', 'status' => 'Failed', 'error' => 'boom']);
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/communication/deliveries?status=Failed');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'Failed');
        $this->assertSame('boom', $response->json('data.0.error'));
    }

    public function test_the_error_field_is_hidden_for_non_failed_deliveries(): void
    {
        $tenant = Tenant::default();
        NotificationDelivery::create(['tenant_id' => $tenant->id, 'type' => NotificationType::NEW_MEMBER, 'channel' => 'email', 'recipient' => 'a@example.com', 'status' => 'Sent']);
        $user = $this->userWithFullAccess(['Settings'], tenantId: $tenant->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/communication/deliveries');

        $this->assertNull($response->json('data.0.error'));
    }

    public function test_a_tenant_never_sees_another_tenants_delivery_history(): void
    {
        $tenantA = Tenant::default();
        $tenantB = $this->otherTenant();
        NotificationDelivery::create(['tenant_id' => $tenantB->id, 'type' => NotificationType::NEW_MEMBER, 'channel' => 'email', 'recipient' => 'other@example.com', 'status' => 'Sent']);
        $userA = $this->userWithFullAccess(['Settings'], tenantId: $tenantA->id);

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/v1/communication/deliveries');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_view_delivery_history(): void
    {
        $this->getJson('/api/v1/communication/deliveries')->assertStatus(401);
    }
}
