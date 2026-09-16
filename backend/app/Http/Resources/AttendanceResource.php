<?php

namespace App\Http\Resources;

use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceRecord */
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberId' => $this->member_id,
            'memberName' => $this->whenLoaded('member', fn () => $this->member?->name),
            'memberAvatar' => $this->whenLoaded('member', fn () => $this->member?->avatar),
            'date' => $this->date,
            'checkIn' => $this->check_in,
            'checkOut' => $this->check_out,
            'duration' => $this->duration,
            'method' => $this->method,
            'status' => $this->check_out ? 'Checked Out' : 'Checked In',
        ];
    }
}
