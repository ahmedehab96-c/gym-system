<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    private const RELATIONS = ['member', 'items', 'payments'];

    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->with(self::RELATIONS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($memberId = $request->integer('member_id')) {
            $query->where('member_id', $memberId);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('issue_date', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('issue_date', '<=', $to);
        }

        $query->orderByDesc('issue_date');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(InvoiceResource::collection($paginator));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);

        $data['issue_date'] ??= now()->toDateString();
        $data['status'] ??= 'Unpaid';

        $invoice = $this->invoices->create($data, $items);
        $invoice->load(self::RELATIONS);

        return ApiResponse::item(new InvoiceResource($invoice), 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(self::RELATIONS);

        return ApiResponse::item(new InvoiceResource($invoice));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);

        $invoice = $this->invoices->update($invoice, $data, $items);
        $invoice->load(self::RELATIONS);

        return ApiResponse::item(new InvoiceResource($invoice));
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return ApiResponse::message('Invoice deleted successfully.');
    }
}
