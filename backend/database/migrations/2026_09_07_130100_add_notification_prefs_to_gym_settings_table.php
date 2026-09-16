<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true)->after('language');
            $table->boolean('notify_push')->default(true)->after('notify_email');
            $table->boolean('notify_sms')->default(false)->after('notify_push');
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_email', 'notify_push', 'notify_sms']);
        });
    }
};
