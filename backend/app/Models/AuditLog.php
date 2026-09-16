<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single platform-admin action, written once and never edited or
 * deleted (see App\Services\PlatformAuditLogger, the only place that
 * creates these). Not tenant-scoped — a Super Admin's own audit trail
 * spans every tenant, same as SubscriptionPlan being a shared catalog.
 */
#[Fillable(['actor_id', 'actor_name', 'action', 'entity_type', 'entity_id', 'tenant_id', 'description'])]
class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
