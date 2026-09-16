<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reuses the Phase 24 device_tokens table for member (mobile) push
 * tokens too — a row belongs to exactly one of user_id/member_id, same
 * convention as the notifications table above.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('member_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
