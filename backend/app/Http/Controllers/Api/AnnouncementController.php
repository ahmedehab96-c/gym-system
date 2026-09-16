<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Http\Requests\UploadAnnouncementImageRequest;
use App\Http\Resources\AnnouncementResource;
use App\Http\Responses\ApiResponse;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    private const RELATIONS = ['plan'];

    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()->with(self::RELATIONS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($audience = $request->string('audience')->toString()) {
            $query->where('audience', $audience);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $query->orderByDesc('publish_date')->orderByDesc('id');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(AnnouncementResource::collection($paginator));
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Draft';

        $announcement = Announcement::create($data);
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement), 201);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->validated());
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement));
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->deleteStoredFile($announcement->image);
        $announcement->delete();

        return ApiResponse::message('Announcement deleted successfully.');
    }

    public function uploadImage(UploadAnnouncementImageRequest $request, Announcement $announcement): JsonResponse
    {
        $this->deleteStoredFile($announcement->image);

        $path = $request->file('image')->store('announcements', 'public');
        $announcement->update(['image' => Storage::disk('public')->url($path)]);
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement));
    }

    public function publish(Announcement $announcement): JsonResponse
    {
        $announcement->update(['status' => 'Published']);
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement));
    }

    public function unpublish(Announcement $announcement): JsonResponse
    {
        $announcement->update(['status' => 'Draft']);
        $announcement->load(self::RELATIONS);

        return ApiResponse::item(new AnnouncementResource($announcement));
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $prefix = '/storage/';

        if (str_starts_with($path, $prefix)) {
            Storage::disk('public')->delete(substr($path, strlen($prefix)));
        }
    }
}
