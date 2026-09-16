<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->enum('audience', ['All Members', 'Trainers', 'Staff', 'Specific Plan'])->default('All Members');
            $table->foreignId('plan_id')->nullable()->constrained('membership_plans')->nullOnDelete();
            $table->enum('status', ['Published', 'Scheduled', 'Draft'])->default('Draft');
            $table->date('publish_date');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
