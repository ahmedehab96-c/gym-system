<?php

namespace App\Services\Communication\DTO;

final readonly class WhatsAppSendResult
{
    public function __construct(
        public string $messageId,
        public string $status,
    ) {}
}
