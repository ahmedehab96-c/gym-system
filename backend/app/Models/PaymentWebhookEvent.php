<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Not tenant-scoped (no BelongsToTenant) — see the migration docblock.
 * Written exclusively from App\Http\Controllers\Api\Payment\PaymentWebhookController.
 */
#[Fillable(['provider', 'event_id', 'type', 'status', 'payload'])]
class PaymentWebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
