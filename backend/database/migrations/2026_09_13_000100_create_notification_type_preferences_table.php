<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-(tenant, notification type, channel) overrides — checked before
 * falling back to the tenant's blanket gym_settings.notify_* toggle (see
 * App\Services\NotificationPreferenceService). A missing row means "use
 * the tenant's channel default," so most tenants never need any rows
 * here at all — this table only stores explicit deviations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_type_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->enum('channel', ['email', 'whatsapp', 'push']);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_type_preferences');
    }
};
