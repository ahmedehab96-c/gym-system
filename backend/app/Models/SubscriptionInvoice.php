<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SubscriptionInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single billing-history entry for a tenant's SaaS subscription.
 * Unrelated to App\Models\Invoice, which bills a gym's own members.
 *
 * tenant_id is fillable for the same reason as TenantSubscription's: rows
 * are created from SubscriptionService, which also runs outside any HTTP
 * request (console/seeders), and no FormRequest exposes this field.
 */
#[Fillable([
    'tenant_id', 'tenant_subscription_id', 'plan_id', 'invoice_number', 'amount', 'currency',
    'billing_period_start', 'billing_period_end', 'status', 'issue_date', 'due_date', 'paid_date',
])]
class SubscriptionInvoice extends Model
{
    /** @use HasFactory<SubscriptionInvoiceFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
