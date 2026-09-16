<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionInvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only, cross-tenant billing history — every SaaS invoice ever
 * issued to any gym. The real payment gateway doesn't exist yet (see
 * SubscriptionService), so this is a ledger view, not a place to
 * trigger charges/refunds from.
 */
class PlatformBillingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionInvoice::query()->with(['tenant', 'plan']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($tenantId = $request->integer('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($from = $request->string('from')->toString()) {
            $query->whereDate('issue_date', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->whereDate('issue_date', '<=', $to);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $query->orderByDesc('issue_date');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(SubscriptionInvoiceResource::collection($paginator));
    }
}
