<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds tenant_id to every existing tenant-owned table and backfills it
 * onto the single default development tenant, per the Phase 18 migration
 * strategy: never delete existing data, always attach it to a real
 * tenant instead.
 *
 * tenant_id is added nullable (not NOT NULL) deliberately: changing an
 * existing column's nullability requires doctrine/dbal, which this
 * project doesn't depend on and Phase 18 isn't meant to introduce. Every
 * write path already goes through BelongsToTenant's `creating` hook
 * (application code) or a factory default (tests/seeders), so tenant_id
 * is always populated in practice; TenantScope simply never matches a
 * null tenant_id against a specific tenant, so a row that somehow ended
 * up without one would be invisible to every tenant rather than leaked.
 */
return new class extends Migration
{
    private const TABLES = [
        'members', 'membership_plans', 'memberships', 'attendance_records',
        'trainers', 'training_programs', 'personal_training_sessions',
        'gym_classes', 'equipment', 'maintenance_records', 'facilities',
        'invoices', 'payments', 'expenses', 'notifications', 'announcements',
        'gym_settings',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $defaultTenantId = Tenant::default()->id;

        foreach (self::TABLES as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        // One settings row per gym, mirroring the pre-multi-tenant
        // "singleton" assumption but scoped per tenant instead of global.
        Schema::table('gym_settings', function (Blueprint $blueprint) {
            $blueprint->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $blueprint) {
            $blueprint->dropUnique(['tenant_id']);
        });

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
