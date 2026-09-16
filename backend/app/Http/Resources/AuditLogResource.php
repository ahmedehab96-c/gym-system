<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actorId' => $this->actor_id,
            'actorName' => $this->actor_name,
            'action' => $this->action,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ] : null),
            'description' => $this->description,
            'createdAt' => $this->created_at,
        ];
    }
}
