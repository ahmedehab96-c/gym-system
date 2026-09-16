<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AppNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * tenant_id is fillable here (unlike most tenant-owned models) for the
 * same reason as TenantSubscription/PaymentTransaction: rows are
 * created from services (NotificationService, AppNotificationChannel),
 * including from console/scheduled commands with no ambient tenant
 * context to auto-fill from — see BelongsToTenant's creating hook. No
 * FormRequest ever exposes this field.
 */
#[Fillable(['tenant_id', 'type', 'title', 'message', 'read', 'user_id', 'member_id'])]
class AppNotification extends Model
{
    /** @use HasFactory<AppNotificationFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'notifications';

    protected function casts(): array
    {
        return [
            'read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A member-facing notification (Phase 25) — mutually exclusive with user_id, see the migration. */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
