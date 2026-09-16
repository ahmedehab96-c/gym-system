<?php

namespace App\Http\Resources;

use App\Models\MemberNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MemberNote */
class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'date' => $this->created_at,
            'text' => $this->text,
        ];
    }
}
