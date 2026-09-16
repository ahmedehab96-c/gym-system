<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Raised for any AI-provider failure — missing configuration, a network
 * timeout, or a non-2xx/unparseable response. Rendered as a 502 by the
 * handler registered in bootstrap/app.php so every AI controller can let
 * this bubble up rather than each writing its own try/catch.
 */
class AIProviderException extends RuntimeException {}
