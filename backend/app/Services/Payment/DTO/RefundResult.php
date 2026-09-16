<?php

namespace App\Services\Payment\DTO;

final readonly class RefundResult
{
    public function __construct(
        public string $reference,
        public string $status,
        /** In the smallest currency unit (e.g. cents), as the gateway reports it. */
        public int $amountMinorUnits,
        public string $currency,
    ) {}
}
