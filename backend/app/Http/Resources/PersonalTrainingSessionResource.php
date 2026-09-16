<?php

namespace App\Http\Resources;

use App\Models\PersonalTrainingSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonalTrainingSession */
class PersonalTrainingSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberId' => $this->member_id,
            'memberName' => $this->whenLoaded('member', fn () => $this->member?->name),
            'memberAvatar' => $this->whenLoaded('member', fn () => $this->member?->avatar),
            'trainerId' => $this->trainer_id,
            'trainerName' => $this->whenLoaded('trainer', fn () => $this->trainer?->name),
            'goal' => $this->goal,
            'sessionsPerWeek' => $this->sessions_per_week,
        ];
    }
}
