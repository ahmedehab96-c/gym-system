<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MembershipPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'tagline', 'price', 'duration_label', 'duration_days', 'features', 'status', 'color', 'popular'])]
class MembershipPlan extends Model
{
    /** @use HasFactory<MembershipPlanFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'popular' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'plan_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'plan_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'plan_id');
    }
}
