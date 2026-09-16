<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes Member a second authenticatable entity alongside User (Phase 25
 * — the Flutter Member Mobile App). Nullable: existing members created
 * by staff have no password until they complete a first-login/password-
 * set flow; a null password can never match Hash::check(), so such a
 * member simply cannot log in yet — not a security gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('last_login_at')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['password', 'last_login_at']);
        });
    }
};
