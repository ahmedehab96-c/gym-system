<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Staff (users) belong to a tenant just like every other tenant-owned
 * table — except tenant_id stays nullable by design here too, not just
 * for the doctrine/dbal reason (see the previous migration): a future
 * platform-level Super Admin (is_platform_admin = true) manages multiple
 * gyms and isn't tied to any single one, so tenant_id = null is a valid,
 * permanent state for that kind of user, not just a migration artifact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->boolean('is_platform_admin')->default(false)->after('role');
        });

        $defaultTenantId = Tenant::default()->id;

        DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('is_platform_admin');
        });
    }
};
