<?php

namespace App\Http\Resources;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentTransaction */
class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'type' => $this->type,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'paidAt' => $this->paid_at,
            'planName' => $this->metadata['plan_name'] ?? null,
            'billingCycle' => $this->metadata['billing_cycle'] ?? null,
            'invoiceNumber' => $this->whenLoaded('invoice', fn () => $this->invoice?->invoice_number),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'slug' => $this->tenant->slug,
            ] : null),
            'createdAt' => $this->created_at,
        ];
    }
}
