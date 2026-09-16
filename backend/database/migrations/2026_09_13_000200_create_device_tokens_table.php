<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Push-notification device tokens for future Flutter/mobile clients
 * (Phase 24 §3) — no mobile app exists yet, this only prepares the
 * server-side storage + API. A token is unique across the whole
 * platform (the same physical device can only be registered once), and
 * always belongs to exactly one tenant + user, never guessed from a
 * request body — see DeviceTokenController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
