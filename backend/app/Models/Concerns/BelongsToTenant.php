<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as tenant-owned: every query is automatically scoped to
 * the current tenant (see TenantScope), and new records are automatically
 * stamped with the current tenant's id unless one was already set
 * explicitly (factories in tests do this; real controller code never
 * does, so this is what assigns tenant_id there).
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->tenant_id !== null) {
                return;
            }

            $tenant = app(CurrentTenant::class);

            if ($tenant->resolved() && $tenant->id() !== null) {
                $model->tenant_id = $tenant->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
