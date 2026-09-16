<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = Payment::query()
            ->where('member_id', $request->user()->id)
            ->orderByDesc('date')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(PaymentResource::collection($paginator));
    }
}
