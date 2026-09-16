<?php

namespace App\Http\Resources;

use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrainingProgram */
class TrainingProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'duration' => $this->duration,
            'difficulty' => $this->difficulty,
            'trainerId' => $this->trainer_id,
            'trainerName' => $this->whenLoaded('trainer', fn () => $this->trainer?->name),
            'membersEnrolled' => $this->when($this->members_enrolled_count !== null, fn () => $this->members_enrolled_count),
            'status' => $this->status,
            'enrolledMembers' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'avatar' => $member->avatar,
                'enrolledAt' => $member->pivot->enrolled_at,
            ])->values()),
        ];
    }
}
