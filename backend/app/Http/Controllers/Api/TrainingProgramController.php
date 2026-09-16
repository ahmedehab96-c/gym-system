<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollMemberRequest;
use App\Http\Requests\StoreTrainingProgramRequest;
use App\Http\Requests\UpdateTrainingProgramRequest;
use App\Http\Requests\UploadProgramImageRequest;
use App\Http\Resources\TrainingProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrainingProgramController extends Controller
{
    private const RELATIONS = ['trainer'];

    private const COUNTS = ['members as members_enrolled_count'];

    public function index(Request $request): JsonResponse
    {
        $query = TrainingProgram::query()->with(self::RELATIONS)->withCount(self::COUNTS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($difficulty = $request->string('difficulty')->toString()) {
            $query->where('difficulty', $difficulty);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        $query->orderBy('name');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(TrainingProgramResource::collection($paginator));
    }

    public function store(StoreTrainingProgramRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Draft';
        $data['difficulty'] ??= 'All Levels';

        $program = TrainingProgram::create($data);
        $program->load(self::RELATIONS)->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($program), 201);
    }

    public function show(TrainingProgram $trainingProgram): JsonResponse
    {
        $trainingProgram->load([...self::RELATIONS, 'members'])->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($trainingProgram));
    }

    public function update(UpdateTrainingProgramRequest $request, TrainingProgram $trainingProgram): JsonResponse
    {
        $trainingProgram->update($request->validated());
        $trainingProgram->load(self::RELATIONS)->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($trainingProgram));
    }

    public function destroy(TrainingProgram $trainingProgram): JsonResponse
    {
        $trainingProgram->delete();

        return ApiResponse::message('Training program deleted successfully.');
    }

    public function uploadImage(UploadProgramImageRequest $request, TrainingProgram $trainingProgram): JsonResponse
    {
        $this->deleteStoredFile($trainingProgram->image);

        $path = $request->file('image')->store('programs', 'public');
        $trainingProgram->update(['image' => Storage::disk('public')->url($path)]);
        $trainingProgram->load(self::RELATIONS)->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($trainingProgram));
    }

    public function enroll(EnrollMemberRequest $request, TrainingProgram $trainingProgram): JsonResponse
    {
        $memberId = $request->validated('member_id');

        if ($trainingProgram->members()->wherePivot('member_id', $memberId)->exists()) {
            return ApiResponse::error('This member is already enrolled in this program.', [], 422);
        }

        $trainingProgram->members()->attach($memberId, ['enrolled_at' => now()]);
        $trainingProgram->load([...self::RELATIONS, 'members'])->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($trainingProgram), 201);
    }

    public function unenroll(TrainingProgram $trainingProgram, Member $member): JsonResponse
    {
        $trainingProgram->members()->detach($member->id);
        $trainingProgram->load([...self::RELATIONS, 'members'])->loadCount(self::COUNTS);

        return ApiResponse::item(new TrainingProgramResource($trainingProgram));
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
