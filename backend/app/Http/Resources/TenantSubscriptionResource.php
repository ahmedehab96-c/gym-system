<?php

namespace App\Http\Resources;

use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TenantSubscription */
class TenantSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'billingCycle' => $this->billing_cycle,
            'price' => $this->price,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ] : null),
            'trialStartsAt' => $this->trial_starts_at,
            'trialEndsAt' => $this->trial_ends_at,
            'startedAt' => $this->started_at,
            'nextBillingAt' => $this->next_billing_at,
            'cancelledAt' => $this->cancelled_at,
            'expiresAt' => $this->expires_at,
        ];
    }
}
