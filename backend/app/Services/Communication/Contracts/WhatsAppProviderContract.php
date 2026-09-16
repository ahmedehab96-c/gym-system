<?php

namespace App\Services\Communication\Contracts;

use App\Services\Communication\DTO\WhatsAppSendResult;

/**
 * Kept provider-independent the same way AIProviderContract and
 * PaymentGatewayContract are (see App\Services\AI, App\Services\Payment)
 * — every business caller depends on this contract only, never a
 * concrete provider. sendTemplate() (not a free-form message) because
 * WhatsApp Business's official API only allows businesses to initiate a
 * conversation with a pre-approved message template — see Phase 24 §4
 * "Do NOT bypass official APIs."
 */
interface WhatsAppProviderContract
{
    /**
     * @param  string  $to  E.164 phone number (e.g. "+15551234567")
     * @param  array<string, string>  $params  Template variable substitutions, in template order
     */
    public function sendTemplate(string $to, string $templateName, array $params): WhatsAppSendResult;
}
