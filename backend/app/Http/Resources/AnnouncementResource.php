<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'audience' => $this->audience,
            'planId' => $this->plan_id,
            'planName' => $this->whenLoaded('plan', fn () => $this->plan?->name),
            'status' => $this->status,
            'publishDate' => $this->publish_date,
        ];
    }
}
