<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundPaymentRequest;
use App\Http\Resources\PaymentTransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionInvoice;
use App\Services\Payment\Exceptions\RefundNotAllowedException;
use App\Services\Payment\RefundService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cross-tenant payment transaction ledger + revenue stats + refunds for
 * the Super Admin Platform Dashboard (Phase 23 §8), alongside the
 * existing (read-only) PlatformBillingController, which lists
 * SubscriptionInvoice rows — this controller is the transaction/money-
 * movement view and the only place a refund can be triggered from.
 */
class PlatformPaymentController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly PlatformAuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->with(['tenant', 'invoice']);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($tenantId = $request->integer('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($from = $request->string('from')->toString()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $query->orderByDesc('created_at');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(PaymentTransactionResource::collection($paginator));
    }

    public function stats(): JsonResponse
    {
        $totalRevenue = (int) PaymentTransaction::where('type', 'Charge')->where('status', 'Paid')->sum('amount');
        $totalRefunded = (int) PaymentTransaction::where('type', 'Refund')->where('status', 'Paid')->sum('amount');
        $successfulPayments = PaymentTransaction::where('type', 'Charge')->where('status', 'Paid')->count();
        $failedPayments = PaymentTransaction::where('type', 'Charge')->where('status', 'Failed')->count();
        $refundCount = PaymentTransaction::where('type', 'Refund')->count();

        $revenueByPlan = SubscriptionInvoice::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscription_invoices.plan_id')
            ->where('subscription_invoices.status', 'Paid')
            ->groupBy('subscription_plans.name')
            ->orderByDesc(DB::raw('SUM(subscription_invoices.amount)'))
            ->get(['subscription_plans.name as plan', DB::raw('SUM(subscription_invoices.amount) as revenue')]);

        $revenueByCycle = SubscriptionInvoice::query()
            ->join('tenant_subscriptions', 'tenant_subscriptions.id', '=', 'subscription_invoices.tenant_subscription_id')
            ->where('subscription_invoices.status', 'Paid')
            ->groupBy('tenant_subscriptions.billing_cycle')
            ->get(['tenant_subscriptions.billing_cycle as cycle', DB::raw('SUM(subscription_invoices.amount) as revenue')]);

        return ApiResponse::item([
            'totalRevenue' => $totalRevenue,
            'totalRefunded' => $totalRefunded,
            'netRevenue' => $totalRevenue - $totalRefunded,
            'successfulPayments' => $successfulPayments,
            'failedPayments' => $failedPayments,
            'refundCount' => $refundCount,
            'revenueByPlan' => $revenueByPlan,
            'revenueByBillingCycle' => $revenueByCycle,
        ]);
    }

    public function refund(RefundPaymentRequest $request, PaymentTransaction $payment_transaction): JsonResponse
    {
        try {
            $refund = $this->refunds->refund($payment_transaction, $request->validated('amount'));
        } catch (RefundNotAllowedException $e) {
            return ApiResponse::error($e->getMessage(), [], 422);
        }

        $this->auditLogger->log(
            $request->user(),
            'billing.refund_issued',
            "Issued a refund of {$refund->currency} {$refund->amount} for transaction {$payment_transaction->reference}.",
            'PaymentTransaction',
            $payment_transaction->id,
            $payment_transaction->tenant_id,
        );

        return ApiResponse::item(new PaymentTransactionResource($refund->load('tenant')));
    }
}
