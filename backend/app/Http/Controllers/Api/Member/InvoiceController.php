<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $paginator = Invoice::query()
            ->with('items')
            ->where('member_id', $request->user()->id)
            ->orderByDesc('issue_date')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(InvoiceResource::collection($paginator));
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if ($invoice->member_id !== $request->user()->id) {
            return ApiResponse::error('Invoice not found.', [], 404);
        }

        $invoice->load(['items', 'payments']);

        return ApiResponse::item(new InvoiceResource($invoice));
    }
}
