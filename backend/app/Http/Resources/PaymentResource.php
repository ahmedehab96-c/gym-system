<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => 'PAY-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'invoiceId' => $this->invoice_id,
            'memberId' => $this->member_id,
            'memberName' => $this->whenLoaded('member', fn () => $this->member?->name),
            'memberAvatar' => $this->whenLoaded('member', fn () => $this->member?->avatar),
            'amount' => $this->amount,
            'method' => $this->method,
            'date' => $this->date,
            'status' => $this->status,
        ];
    }
}
