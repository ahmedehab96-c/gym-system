<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferenceRequest;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Communication preferences for the caller's own tenant (Phase 24 §5) —
 * gated by the existing `Settings` permission module, same as
 * GymSettingController, rather than a new module.
 */
class NotificationPreferenceController extends Controller
{
    public function __construct(private readonly NotificationPreferenceService $preferences) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        return ApiResponse::item([
            'channels' => [
                'email' => $this->preferences->channelEnabledForTenant($tenantId, 'email'),
                'whatsapp' => $this->preferences->channelEnabledForTenant($tenantId, 'whatsapp'),
                'push' => $this->preferences->channelEnabledForTenant($tenantId, 'push'),
            ],
            'overrides' => $this->preferences->overridesForTenant($tenantId),
        ]);
    }

    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $this->preferences->setTypePreference(
            $tenantId,
            $request->validated('type'),
            $request->validated('channel'),
            (bool) $request->validated('enabled'),
        );

        return ApiResponse::item([
            'overrides' => $this->preferences->overridesForTenant($tenantId),
        ]);
    }
}
