<?php

namespace App\Http\Resources;

use App\Models\MaintenanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MaintenanceRecord */
class MaintenanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipmentId' => $this->equipment_id,
            'equipmentName' => $this->whenLoaded('equipment', fn () => $this->equipment?->name),
            'type' => $this->type,
            'technician' => $this->technician,
            'date' => $this->date,
            'cost' => $this->cost,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
