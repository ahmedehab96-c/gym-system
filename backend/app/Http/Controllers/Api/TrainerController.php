<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainerRequest;
use App\Http\Requests\UpdateTrainerRequest;
use App\Http\Requests\UploadTrainerPhotoRequest;
use App\Http\Resources\TrainerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainerController extends Controller
{
    private const COUNTS = ['members as assigned_members_count', 'gymClasses as classes_count'];

    private const SORTABLE = [
        'name' => 'name',
        'rating' => 'rating',
        'sessionsCompleted' => 'sessions_completed',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Trainer::query()->withCount(self::COUNTS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($specialty = $request->string('specialty')->toString()) {
            $query->where('specialty', $specialty);
        }

        $sortKey = self::SORTABLE[$request->string('sort_by')->toString()] ?? 'name';
        $sortDir = $request->string('sort_dir')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortKey, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(TrainerResource::collection($paginator));
    }

    public function store(StoreTrainerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Active';
        $data['rating'] ??= 0;
        $data['sessions_completed'] ??= 0;

        $trainer = Trainer::create($data);
        $trainer->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainerResource($trainer), 201);
    }

    public function show(Trainer $trainer): JsonResponse
    {
        $trainer->loadCount(self::COUNTS)->load('members');

        return ApiResponse::item(new TrainerResource($trainer));
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): JsonResponse
    {
        $trainer->update($request->validated());
        $trainer->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainerResource($trainer));
    }

    public function destroy(Trainer $trainer): JsonResponse
    {
        if ($trainer->gymClasses()->exists() || $trainer->personalTrainingSessions()->exists()) {
            return ApiResponse::error(
                'This trainer has scheduled classes or personal training sessions and cannot be deleted.',
                [], 422
            );
        }

        $trainer->delete();

        return ApiResponse::message('Trainer deleted successfully.');
    }

    public function uploadPhoto(UploadTrainerPhotoRequest $request, Trainer $trainer): JsonResponse
    {
        $this->deleteStoredFile($trainer->photo);

        $path = $request->file('photo')->store('trainers', 'public');
        $trainer->update(['photo' => Storage::disk('public')->url($path)]);
        $trainer->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainerResource($trainer));
    }

    public function stats(): JsonResponse
    {
        return ApiResponse::item([
            'total' => Trainer::query()->count(),
            'active' => Trainer::query()->where('status', 'Active')->count(),
            'onLeave' => Trainer::query()->where('status', 'On Leave')->count(),
            'inactive' => Trainer::query()->where('status', 'Inactive')->count(),
            'averageRating' => round((float) (Trainer::query()->avg('rating') ?? 0), 2),
            'totalSessionsCompleted' => (int) Trainer::query()->sum('sessions_completed'),
            'totalAssignedMembers' => Member::query()->whereNotNull('trainer_id')->count(),
        ]);
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
