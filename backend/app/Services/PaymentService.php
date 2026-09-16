<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Creates/updates payments and keeps a linked invoice's status in sync,
 * wrapping any multi-record write (payment + invoice) in a transaction.
 */
class PaymentService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create($data);
            $this->syncInvoice($payment->invoice_id);

            return $payment->fresh();
        });
    }

    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $previousInvoiceId = $payment->invoice_id;

            $payment->update($data);

            $this->syncInvoice($previousInvoiceId);

            if ($payment->invoice_id !== $previousInvoiceId) {
                $this->syncInvoice($payment->invoice_id);
            }

            return $payment->fresh();
        });
    }

    public function refund(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'Refunded']);
            $this->syncInvoice($payment->invoice_id);

            return $payment->fresh();
        });
    }

    private function syncInvoice(?int $invoiceId): void
    {
        if (! $invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);

        if ($invoice) {
            $this->invoices->syncStatusFromPayments($invoice);
        }
    }
}
