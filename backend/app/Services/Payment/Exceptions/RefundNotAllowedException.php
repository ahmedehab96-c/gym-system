<?php

namespace App\Services\Payment\Exceptions;

use RuntimeException;

/** A business-rule refusal (already refunded, not a paid charge, ...) — not a gateway failure, see PaymentGatewayException. */
class RefundNotAllowedException extends RuntimeException {}
