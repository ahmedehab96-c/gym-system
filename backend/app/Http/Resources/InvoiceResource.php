<?php

namespace App\Http\Resources;

use App\Models\GymSetting;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Print/PDF-ready shape: every invoice carries its own line items, computed
 * subtotal/discount/total, payment balance, and a "business" header block
 * (from GymSetting) so a client can render a standalone document from a
 * single response with no extra requests.
 *
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = (int) $this->items->sum('amount');
        $paid = $this->relationLoaded('payments')
            ? (int) $this->payments->where('status', 'Paid')->sum('amount')
            : null;

        return [
            'id' => $this->id,
            'invoiceNumber' => 'INV-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
            'memberId' => $this->member_id,
            'memberName' => $this->whenLoaded('member', fn () => $this->member?->name),
            'memberAvatar' => $this->whenLoaded('member', fn () => $this->member?->avatar),
            'issueDate' => $this->issue_date,
            'dueDate' => $this->due_date,
            'items' => InvoiceItemResource::collection($this->items),
            'subtotal' => $subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'amountPaid' => $paid,
            'balanceDue' => $paid === null ? null : max(0, $this->total - $paid),
            'business' => $this->businessInfo(),
        ];
    }

    private function businessInfo(): array
    {
        $settings = GymSetting::query()->first();

        return [
            'name' => $settings?->name ?? 'Gym',
            'phone' => $settings?->phone,
            'email' => $settings?->email,
            'address' => $settings?->address,
            'currency' => $settings?->currency ?? 'EGP',
        ];
    }
}
