<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates/updates invoices together with their line items inside a single
 * transaction, and keeps `total` (subtotal - discount) and `status` in sync
 * with the items and payments attached to the invoice.
 */
class InvoiceService
{
    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['total'] = 0;
            $invoice = Invoice::create($data);

            $this->replaceItems($invoice, $items);
            $this->recomputeTotal($invoice);

            return $invoice->fresh();
        });
    }

    public function update(Invoice $invoice, array $data, ?array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->update($data);

            if ($items !== null) {
                $this->replaceItems($invoice, $items);
            }

            $this->recomputeTotal($invoice);

            return $invoice->fresh();
        });
    }

    public function recomputeTotal(Invoice $invoice): void
    {
        $subtotal = (int) $invoice->items()->sum('amount');
        $total = max(0, $subtotal - $invoice->discount);

        if ($total !== $invoice->total) {
            $invoice->update(['total' => $total]);
        }

        $this->syncStatusFromPayments($invoice);
    }

    /**
     * Recomputes Paid/Overdue/Unpaid from the invoice's Paid payments,
     * without disturbing a Draft invoice that hasn't been finalized yet.
     */
    public function syncStatusFromPayments(Invoice $invoice): void
    {
        if ($invoice->status === 'Draft') {
            return;
        }

        $paid = (int) $invoice->payments()->where('status', 'Paid')->sum('amount');

        $status = match (true) {
            $invoice->total > 0 && $paid >= $invoice->total => 'Paid',
            Carbon::parse($invoice->due_date)->isPast() => 'Overdue',
            default => 'Unpaid',
        };

        if ($status !== $invoice->status) {
            $invoice->update(['status' => $status]);
        }
    }

    private function replaceItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach (array_values($items) as $index => $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'amount' => $item['amount'],
                'sort_order' => $index,
            ]);
        }
    }
}
