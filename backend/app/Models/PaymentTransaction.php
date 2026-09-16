<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single real money movement (charge or refund) against a tenant's
 * SaaS subscription — see the migration docblock for how this differs
 * from SubscriptionInvoice. tenant_id is fillable for the same reason as
 * TenantSubscription/SubscriptionInvoice: rows are created from
 * App\Services\Payment services, not directly from a FormRequest.
 */
#[Fillable([
    'tenant_id', 'tenant_subscription_id', 'subscription_invoice_id', 'gateway', 'type', 'reference',
    'related_reference', 'gateway_payment_intent_id', 'amount', 'currency', 'status', 'paid_at', 'metadata',
])]
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }
}
