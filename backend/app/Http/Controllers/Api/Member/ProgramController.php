<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingProgramResource;
use App\Http\Responses\ApiResponse;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    private const RELATIONS = ['trainer'];

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = TrainingProgram::query()
            ->with(self::RELATIONS)
            ->where('status', 'Active')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(TrainingProgramResource::collection($paginator));
    }

    public function show(TrainingProgram $program): JsonResponse
    {
        $program->load(self::RELATIONS);

        return ApiResponse::item(new TrainingProgramResource($program));
    }

    /**
     * The authenticated member's own enrolled program(s) — Phase 25 §7
     * "assigned program where supported". The pivot column is qualified
     * as program_enrollments.member_id — Member itself also has its own
     * (unrelated, string) `member_id` business-id column, so leaving
     * this unqualified is an ambiguous-column SQL error once `members`
     * and `program_enrollments` are joined together.
     */
    public function mine(Request $request): JsonResponse
    {
        $programs = TrainingProgram::query()
            ->with(self::RELATIONS)
            ->whereHas('members', fn ($q) => $q->where('program_enrollments.member_id', $request->user()->id))
            ->get();

        return ApiResponse::item(TrainingProgramResource::collection($programs));
    }
}
