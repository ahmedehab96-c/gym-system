<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'Membership Expiring',
                'Payment Received',
                'New Member',
                'Class Reminder',
                'Maintenance Due',
                'System Notification',
            ]);
            $table->string('title');
            $table->text('message');
            $table->boolean('read')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'read']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
