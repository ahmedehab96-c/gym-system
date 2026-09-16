<?php

namespace App\Models\Concerns;

use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a tenant-owned model to the current request's
 * tenant. See App\Support\CurrentTenant for what each resolution state
 * means; this is the only place that decides how to act on them, so
 * individual controllers never need their own "->where('tenant_id', ...)"
 * filters.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(CurrentTenant::class);

        if (! $tenant->resolved() || $tenant->id() === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenant->id());
    }
}
