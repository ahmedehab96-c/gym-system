<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'image', 'capacity', 'area', 'status'])]
class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use BelongsToTenant, HasFactory;
}
