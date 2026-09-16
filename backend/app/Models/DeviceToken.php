<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'user_id', 'member_id', 'token', 'platform'])]
class DeviceToken extends Model
{
    use BelongsToTenant;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A mobile member's device (Phase 25) — mutually exclusive with user_id. */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
