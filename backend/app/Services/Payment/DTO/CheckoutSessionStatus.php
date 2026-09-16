<?php

namespace App\Services\Payment\DTO;

use Illuminate\Support\Carbon;

/**
 * The server-verified truth about a checkout session, fetched directly
 * from the gateway's API — never trust a frontend-reported status; see
 * App\Services\Payment\PaymentVerificationService.
 */
final readonly class CheckoutSessionStatus
{
    public function __construct(
        public string $id,
        public string $status,
        public string $paymentStatus,
        public ?string $subscriptionId,
        public ?string $customerId,
        public ?string $paymentIntentId,
        public ?Carbon $currentPeriodEnd,
    ) {}

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }
}
