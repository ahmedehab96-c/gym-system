<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One AI request, written once by App\Services\AI\AIUsageService and
 * never edited — the same immutable-log shape as App\Models\AuditLog.
 * tenant-scoped like every other tenant-owned model (BelongsToTenant),
 * so a query here can never leak across tenants.
 */
#[Fillable(['tenant_id', 'user_id', 'feature', 'provider', 'model', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'estimated_cost', 'status'])]
class AIUsageLog extends Model
{
    use BelongsToTenant, HasFactory;

    // Eloquent's Str::snake() would otherwise guess 'a_i_usage_logs' —
    // it treats "AI" as two separate capital transitions.
    protected $table = 'ai_usage_logs';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
