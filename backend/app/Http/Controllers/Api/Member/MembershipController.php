<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\MembershipResource;
use App\Http\Responses\ApiResponse;
use App\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $membership = Membership::query()
            ->with('plan')
            ->where('member_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->first();

        if (! $membership) {
            return ApiResponse::error('No membership found.', [], 404);
        }

        return ApiResponse::item(new MembershipResource($membership));
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $paginator = Membership::query()
            ->with('plan')
            ->where('member_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(MembershipResource::collection($paginator));
    }
}
