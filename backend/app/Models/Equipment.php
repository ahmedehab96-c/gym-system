<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'image', 'category', 'brand', 'model', 'purchase_date', 'condition', 'location', 'last_maintenance', 'next_maintenance', 'status'])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'last_maintenance' => 'date',
            'next_maintenance' => 'date',
        ];
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
