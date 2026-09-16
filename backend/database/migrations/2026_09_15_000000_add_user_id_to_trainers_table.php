<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a Trainer roster row to the staff User account that logs in as
 * that trainer (Phase 26 — the Flutter Trainer App). Nullable: a Trainer
 * roster entry can exist with no linked login (e.g. a trainer who hasn't
 * been given app access yet), and a User with role=Trainer can likewise
 * exist before being linked — neither side requires the other to exist
 * first. Unique because a User can only ever be "one" trainer profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
