<?php

namespace App\Services\Payment\DTO;

/** What a provider returns right after a checkout session is created. */
final readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
