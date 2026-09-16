<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/**
 * The only place that writes to audit_logs — every platform controller
 * that mutates cross-tenant state (Gyms, Plans, Subscriptions, Platform
 * Users) goes through here so the trail is consistent and can't be
 * forgotten in one place but not another.
 */
class PlatformAuditLogger
{
    public function log(
        User $actor,
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $tenantId = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'tenant_id' => $tenantId,
            'description' => $description,
        ]);
    }
}
