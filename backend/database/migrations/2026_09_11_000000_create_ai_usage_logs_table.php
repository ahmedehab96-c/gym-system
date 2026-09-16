<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per AI request a tenant makes (Phase 22). Immutable, like
 * audit_logs — see App\Models\AIUsageLog. Drives both
 * App\Services\AI\AIUsageService::canUseAI() (counts this tenant's
 * successful rows for the current period against its plan's
 * ai_requests limit) and the /ai/usage statistics endpoint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature');
            $table->string('provider');
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index('feature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
