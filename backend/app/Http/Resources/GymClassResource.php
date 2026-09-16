<?php

namespace App\Http\Resources;

use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GymClass */
class GymClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'trainerId' => $this->trainer_id,
            'trainerName' => $this->whenLoaded('trainer', fn () => $this->trainer?->name),
            'date' => $this->date,
            'day' => $this->day,
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'duration' => $this->durationLabel(),
            'capacity' => $this->capacity,
            'booked' => $this->bookings_count ?? $this->booked,
            'status' => $this->status,
            'color' => $this->color,
            // Only present for the Flutter Member Mobile App (Phase 25) —
            // omitted entirely for staff requests, so the staff dashboard's
            // response shape is unchanged.
            'isBookedByMe' => $this->when(
                $request->user() instanceof Member,
                fn () => $this->bookings()->where('member_id', $request->user()->id)->exists(),
            ),
            'bookedMembers' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'avatar' => $member->avatar,
                'bookedAt' => $member->pivot->booked_at,
            ])->values()),
        ];
    }

    private function durationLabel(): ?string
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        [$sh, $sm] = array_map('intval', explode(':', $this->start_time));
        [$eh, $em] = array_map('intval', explode(':', $this->end_time));
        $minutes = max(0, ($eh * 60 + $em) - ($sh * 60 + $sm));

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours === 0 ? "{$mins}m" : ($mins === 0 ? "{$hours}h" : "{$hours}h {$mins}m");
    }
}
