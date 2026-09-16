<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = Trainer::query()
            ->where('status', 'Active')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(TrainerResource::collection($paginator));
    }

    public function show(Trainer $trainer): JsonResponse
    {
        return ApiResponse::item(new TrainerResource($trainer));
    }
}
