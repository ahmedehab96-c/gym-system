<?php

namespace App\Services\Communication\DTO;

final readonly class PushSendResult
{
    public function __construct(
        public string $messageId,
        public string $status,
    ) {}
}
