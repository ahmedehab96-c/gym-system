<?php

namespace App\Services\Payment\DTO;

use Illuminate\Support\Carbon;

final readonly class GatewaySubscription
{
    public function __construct(
        public string $id,
        public string $status,
        public Carbon $currentPeriodEnd,
    ) {}
}
