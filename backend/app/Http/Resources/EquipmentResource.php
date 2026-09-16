<?php

namespace App\Http\Resources;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Equipment */
class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'category' => $this->category,
            'brand' => $this->brand,
            'model' => $this->model,
            'purchaseDate' => $this->purchase_date,
            'condition' => $this->condition,
            'location' => $this->location,
            'lastMaintenance' => $this->last_maintenance,
            'nextMaintenance' => $this->next_maintenance,
            'status' => $this->status,
            'maintenanceHistory' => MaintenanceRecordResource::collection($this->whenLoaded('maintenanceRecords')),
        ];
    }
}
