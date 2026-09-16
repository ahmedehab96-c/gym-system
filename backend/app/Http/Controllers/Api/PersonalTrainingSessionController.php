<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonalTrainingSessionRequest;
use App\Http\Requests\UpdatePersonalTrainingSessionRequest;
use App\Http\Resources\PersonalTrainingSessionResource;
use App\Http\Responses\ApiResponse;
use App\Models\PersonalTrainingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalTrainingSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PersonalTrainingSession::query()->with(['member', 'trainer']);

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->orderByDesc('id')->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(PersonalTrainingSessionResource::collection($paginator));
    }

    public function store(StorePersonalTrainingSessionRequest $request): JsonResponse
    {
        $session = PersonalTrainingSession::create($request->validated());
        $session->load(['member', 'trainer']);

        return ApiResponse::item(new PersonalTrainingSessionResource($session), 201);
    }

    public function update(UpdatePersonalTrainingSessionRequest $request, PersonalTrainingSession $personalTraining): JsonResponse
    {
        $personalTraining->update($request->validated());
        $personalTraining->load(['member', 'trainer']);

        return ApiResponse::item(new PersonalTrainingSessionResource($personalTraining));
    }

    public function destroy(PersonalTrainingSession $personalTraining): JsonResponse
    {
        $personalTraining->delete();

        return ApiResponse::message('Personal training session removed.');
    }
}
