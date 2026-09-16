<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `qr_token` holds the raw value encrypted-at-rest (Eloquent
     * `encrypted` cast) — unlike a password, a member legitimately needs
     * it handed back to them (their own "My QR" screen redisplaying the
     * same code), so it can't be one-way hashed like Sanctum tokens are.
     * `qr_token_hash` is a SHA-256 of that same value, plain and unique,
     * so a front-desk scan can be resolved with an indexed lookup without
     * ever decrypting anything (Phase 28 §1/§7).
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->text('qr_token')->nullable()->after('emergency_contact');
            $table->string('qr_token_hash', 64)->nullable()->unique()->after('qr_token');
            $table->timestamp('qr_token_issued_at')->nullable()->after('qr_token_hash');
            $table->timestamp('qr_token_expires_at')->nullable()->after('qr_token_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_token_hash', 'qr_token_issued_at', 'qr_token_expires_at']);
        });
    }
};
