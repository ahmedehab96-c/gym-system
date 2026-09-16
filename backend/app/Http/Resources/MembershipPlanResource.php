<?php

namespace App\Http\Resources;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MembershipPlan */
class MembershipPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'price' => $this->price,
            'duration' => $this->duration_label,
            'durationDays' => $this->duration_days,
            'features' => $this->features ?? [],
            'memberCount' => $this->when(
                $this->members_count !== null,
                fn () => $this->members_count,
            ),
            'status' => $this->status,
            'color' => $this->color,
            'popular' => $this->popular,
        ];
    }
}
