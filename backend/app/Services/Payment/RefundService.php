<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Services\Payment\Contracts\PaymentGatewayContract;
use App\Services\Payment\Exceptions\RefundNotAllowedException;

/**
 * Platform-admin-triggered refunds only — see
 * App\Http\Controllers\Api\Platform\PlatformPaymentController::refund(),
 * which is the sole caller and already sits behind the platform.admin
 * gate. A gym tenant never refunds itself.
 */
class RefundService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly PaymentFinalizer $finalizer,
    ) {}

    public function refund(PaymentTransaction $original, ?int $amount = null): PaymentTransaction
    {
        if ($original->type !== 'Charge' || $original->status !== 'Paid') {
            throw new RefundNotAllowedException('Only a paid charge can be refunded.');
        }

        if (! $original->gateway_payment_intent_id) {
            throw new RefundNotAllowedException('This transaction has no gateway payment reference to refund.');
        }

        $alreadyRefunded = PaymentTransaction::where('related_reference', $original->reference)
            ->where('type', 'Refund')
            ->exists();

        if ($alreadyRefunded) {
            throw new RefundNotAllowedException('This transaction has already been refunded.');
        }

        $result = $this->gateway->refund(
            $original->gateway_payment_intent_id,
            $amount !== null ? $amount * 100 : null,
        );

        return $this->finalizer->recordRefund($original, $result->reference, $result->amountMinorUnits, $result->currency);
    }
}
