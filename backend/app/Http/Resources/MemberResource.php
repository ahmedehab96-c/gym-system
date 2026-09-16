<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Member */
class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberId' => $this->member_id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'dob' => $this->dob,
            'joinDate' => $this->join_date,
            'planId' => $this->plan_id,
            'planName' => $this->whenLoaded('plan', fn () => $this->plan?->name),
            'startDate' => $this->start_date,
            'expiryDate' => $this->expiry_date,
            'status' => $this->status,
            'attendanceRate' => $this->attendance_rate,
            'trainerId' => $this->trainer_id,
            'trainerName' => $this->whenLoaded('trainer', fn () => $this->trainer?->name),
            'balanceDue' => $this->balance_due,
            'notes' => NoteResource::collection($this->whenLoaded('notes')),
            'emergencyContact' => $this->emergency_contact,
        ];
    }
}
