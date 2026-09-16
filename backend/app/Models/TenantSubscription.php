<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TenantSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unlike most tenant-owned models, tenant_id IS fillable here: rows are
 * created from App\Services\SubscriptionService, which also runs from
 * console commands/seeders (no ambient tenant context to auto-fill
 * from — see BelongsToTenant's creating hook). No FormRequest ever
 * exposes a tenant_id field for this resource, so this doesn't open any
 * mass-assignment surface to end-user input.
 */
#[Fillable([
    'tenant_id', 'plan_id', 'status', 'billing_cycle', 'price', 'trial_starts_at', 'trial_ends_at',
    'started_at', 'next_billing_at', 'cancelled_at', 'expires_at',
    'gateway', 'gateway_customer_id', 'gateway_subscription_id',
])]
class TenantSubscription extends Model
{
    /** @use HasFactory<TenantSubscriptionFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'trial_starts_at' => 'date',
            'trial_ends_at' => 'date',
            'started_at' => 'date',
            'next_billing_at' => 'date',
            'cancelled_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
