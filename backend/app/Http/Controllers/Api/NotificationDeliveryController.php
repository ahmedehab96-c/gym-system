<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationDeliveryResource;
use App\Http\Responses\ApiResponse;
use App\Models\NotificationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only external-channel delivery history/failures for the caller's
 * own tenant (Phase 24 §8) — gated by the `Settings` permission module.
 */
class NotificationDeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationDelivery::query()->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(NotificationDeliveryResource::collection($paginator));
    }
}
