<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()->where('member_id', $request->user()->id);

        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $paginator = $query->orderByDesc('created_at')->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(NotificationResource::collection($paginator));
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->member_id !== $request->user()->id) {
            return ApiResponse::error('Notification not found.', [], 404);
        }

        $notification->update(['read' => true]);

        return ApiResponse::item(new NotificationResource($notification));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::query()->where('member_id', $request->user()->id)->where('read', false)->update(['read' => true]);

        return ApiResponse::message('All notifications marked as read.');
    }
}
