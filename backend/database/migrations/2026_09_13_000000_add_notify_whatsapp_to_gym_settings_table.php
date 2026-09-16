<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->boolean('notify_whatsapp')->default(false)->after('notify_sms');
        });
    }

    public function down(): void
    {
        Schema::table('gym_settings', function (Blueprint $table) {
            $table->dropColumn('notify_whatsapp');
        });
    }
};
