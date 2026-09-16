<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A gym, as seen from the platform admin's Gym Management screens.
 * Relation/count fields only appear when the controller actually
 * loaded them (index eager-loads a lighter set than show).
 *
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logo' => $this->logo,
            'status' => $this->status,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'createdAt' => $this->created_at,
            'staffCount' => $this->whenCounted('users'),
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
                'phone' => $this->owner->phone,
            ] : null),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'status' => $this->subscription->status,
                'billingCycle' => $this->subscription->billing_cycle,
                'planName' => $this->subscription->plan?->name,
                'nextBillingAt' => $this->subscription->next_billing_at,
            ] : null),
            'usage' => $this->when(isset($this->usage_members), fn () => [
                'members' => $this->usage_members,
                'staff' => $this->usage_staff,
                'trainers' => $this->usage_trainers,
            ]),
            'revenue' => $this->when(isset($this->usage_revenue), fn () => (int) $this->usage_revenue),
        ];
    }
}
