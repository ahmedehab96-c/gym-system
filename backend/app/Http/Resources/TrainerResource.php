<?php

namespace App\Http\Resources;

use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trainer */
class TrainerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'name' => $this->name,
            'photo' => $this->photo,
            'specialty' => $this->specialty,
            'specialties' => $this->specialties ?? [],
            'experience' => $this->experience,
            'phone' => $this->phone,
            'email' => $this->email,
            'bio' => $this->bio,
            'assignedMembers' => $this->when($this->assigned_members_count !== null, fn () => $this->assigned_members_count),
            'classesCount' => $this->when($this->classes_count !== null, fn () => $this->classes_count),
            'status' => $this->status,
            'rating' => (float) $this->rating,
            'sessionsCompleted' => $this->sessions_completed,
            'schedule' => $this->schedule ?? [],
            'assignedMembersList' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'avatar' => $member->avatar,
                'email' => $member->email,
                'status' => $member->status,
            ])->values()),
        ];
    }
}
