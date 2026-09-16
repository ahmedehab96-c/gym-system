<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Platform-visibility into which communication providers are configured
 * (Phase 24 §9). Provider credentials are environment-only (same
 * pattern as config/payment.php's Stripe keys — see that phase's
 * precedent), so there is nothing to mutate through an API; this is a
 * deliberately read-only status view. Never returns an actual secret
 * value, only whether one is present, and which non-sensitive provider/
 * driver is selected.
 */
class PlatformCommunicationController extends Controller
{
    public function status(): JsonResponse
    {
        return ApiResponse::item([
            'email' => [
                'driver' => config('mail.default'),
                'configured' => config('mail.default') !== 'log' && config('mail.default') !== 'array',
            ],
            'whatsapp' => [
                'provider' => config('communication.whatsapp_provider'),
                'configured' => filled(config('communication.whatsapp.whatsapp_cloud_api.access_token'))
                    && filled(config('communication.whatsapp.whatsapp_cloud_api.phone_number_id')),
            ],
            'push' => [
                'provider' => config('communication.push_provider'),
                'configured' => filled(config('communication.push.fcm.access_token'))
                    && filled(config('communication.push.fcm.project_id')),
            ],
        ]);
    }
}
