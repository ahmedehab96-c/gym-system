<?php

namespace App\Http\Resources;

use App\Models\SubscriptionInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionInvoice */
class SubscriptionInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoice_number,
            'planName' => $this->whenLoaded('plan', fn () => $this->plan?->name),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ] : null),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'billingPeriodStart' => $this->billing_period_start,
            'billingPeriodEnd' => $this->billing_period_end,
            'status' => $this->status,
            'issueDate' => $this->issue_date,
            'dueDate' => $this->due_date,
            'paidDate' => $this->paid_date,
        ];
    }
}
