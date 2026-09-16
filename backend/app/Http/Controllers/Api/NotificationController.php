<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Models\AppNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * A user's notification inbox is their own targeted notifications
     * plus every global (unassigned) system notification.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->visibleTo($request)->orderByDesc('created_at')->orderByDesc('id');

        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(NotificationResource::collection($paginator));
    }

    public function update(Request $request, AppNotification $notification): JsonResponse
    {
        if ($error = $this->authorizeOwnership($request, $notification)) {
            return $error;
        }

        $notification->update(['read' => $request->boolean('read', true)]);

        return ApiResponse::item(new NotificationResource($notification));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->visibleTo($request)->where('read', false)->update(['read' => true]);

        return ApiResponse::message('All notifications marked as read.');
    }

    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        if ($error = $this->authorizeOwnership($request, $notification)) {
            return $error;
        }

        $notification->delete();

        return ApiResponse::message('Notification deleted successfully.');
    }

    private function visibleTo(Request $request): Builder
    {
        $userId = $request->user()->id;

        return AppNotification::query()->where(function (Builder $query) use ($userId) {
            $query->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }

    private function authorizeOwnership(Request $request, AppNotification $notification): ?JsonResponse
    {
        if ($notification->user_id !== null && $notification->user_id !== $request->user()->id) {
            return ApiResponse::error('You do not have permission to modify this notification.', [], 403);
        }

        return null;
    }
}
