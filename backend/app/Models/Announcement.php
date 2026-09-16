<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'description', 'image', 'audience', 'plan_id', 'status', 'publish_date'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'publish_date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }
}
