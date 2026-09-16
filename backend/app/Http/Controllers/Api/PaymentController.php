<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    private const RELATIONS = ['member'];

    public function __construct(
        private readonly PaymentService $payments,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->filteredQuery($request)->paginate($this->perPage($request))->appends($request->query());

        return ApiResponse::paginated(PaymentResource::collection($paginator));
    }

    public function forMember(Request $request, Member $member): JsonResponse
    {
        $paginator = $this->filteredQuery($request)
            ->where('member_id', $member->id)
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return ApiResponse::paginated(PaymentResource::collection($paginator));
    }

    public function stats(): JsonResponse
    {
        $today = Carbon::today();
        $paid = Payment::query()->where('status', 'Paid');

        return ApiResponse::item([
            'todayRevenue' => (int) (clone $paid)->whereDate('date', $today)->sum('amount'),
            'monthlyRevenue' => (int) (clone $paid)->whereBetween('date', [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ])->sum('amount'),
            'pending' => Payment::query()->where('status', 'Pending')->count(),
            'refunds' => Payment::query()->where('status', 'Refunded')->count(),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['date'] ??= Carbon::today()->toDateString();
        $data['status'] ??= 'Pending';
        $data['method'] ??= 'Cash';

        $payment = $this->payments->create($data);
        $payment->load(self::RELATIONS);
        $this->notifyStatus($payment);

        return ApiResponse::item(new PaymentResource($payment), 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(self::RELATIONS);

        return ApiResponse::item(new PaymentResource($payment));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $previousStatus = $payment->status;

        $payment = $this->payments->update($payment, $request->validated());
        $payment->load(self::RELATIONS);

        if ($payment->status !== $previousStatus) {
            $this->notifyStatus($payment);
        }

        return ApiResponse::item(new PaymentResource($payment));
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();

        return ApiResponse::message('Payment deleted successfully.');
    }

    public function refund(Payment $payment): JsonResponse
    {
        if ($payment->status !== 'Paid') {
            return ApiResponse::error('Only paid payments can be refunded.', [], 422);
        }

        $payment = $this->payments->refund($payment);
        $payment->load(self::RELATIONS);

        return ApiResponse::item(new PaymentResource($payment));
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Payment::query()->with(self::RELATIONS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($method = $request->string('method')->toString()) {
            $query->where('method', $method);
        }

        if ($memberId = $request->integer('member_id')) {
            $query->where('member_id', $memberId);
        }

        if ($invoiceId = $request->integer('invoice_id')) {
            $query->where('invoice_id', $invoiceId);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('date', '<=', $to);
        }

        return $query->orderByDesc('date');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }

    private function notifyStatus(Payment $payment): void
    {
        match ($payment->status) {
            'Paid' => $this->notifications->paymentReceived($payment),
            'Pending' => $this->notifications->paymentPending($payment),
            'Failed' => $this->notifications->paymentFailed($payment),
            default => null,
        };
    }
}
