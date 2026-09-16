<?php

namespace App\Http\Resources;

use App\Models\Membership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Membership */
class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberId' => $this->member_id,
            'memberName' => $this->whenLoaded('member', fn () => $this->member?->name),
            'memberAvatar' => $this->whenLoaded('member', fn () => $this->member?->avatar),
            'planId' => $this->plan_id,
            'planName' => $this->whenLoaded('plan', fn () => $this->plan?->name),
            'startDate' => $this->start_date,
            'expiryDate' => $this->expiry_date,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
