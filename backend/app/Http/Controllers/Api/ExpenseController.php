<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Requests\UploadExpenseReceiptRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Responses\ApiResponse;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($from = $request->string('date_from')->toString()) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to = $request->string('date_to')->toString()) {
            $query->whereDate('date', '<=', $to);
        }

        $query->orderByDesc('date');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(ExpenseResource::collection($paginator));
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['date'] ??= Carbon::today()->toDateString();

        $expense = Expense::create($data);

        return ApiResponse::item(new ExpenseResource($expense), 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        return ApiResponse::item(new ExpenseResource($expense));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $expense->update($request->validated());

        return ApiResponse::item(new ExpenseResource($expense));
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->deleteStoredFile($expense->receipt);
        $expense->delete();

        return ApiResponse::message('Expense deleted successfully.');
    }

    public function uploadReceipt(UploadExpenseReceiptRequest $request, Expense $expense): JsonResponse
    {
        $this->deleteStoredFile($expense->receipt);

        $path = $request->file('receipt')->store('receipts', 'public');
        $expense->update(['receipt' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new ExpenseResource($expense));
    }

    public function stats(): JsonResponse
    {
        $today = Carbon::today();

        $thisMonth = Expense::query()->whereBetween('date', [
            $today->copy()->startOfMonth()->toDateString(),
            $today->copy()->endOfMonth()->toDateString(),
        ]);

        $byCategory = Expense::query()
            ->selectRaw('category, SUM(amount) as amount')
            ->groupBy('category')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'amount' => (int) $row->amount])
            ->values();

        return ApiResponse::item([
            'total' => (int) Expense::query()->sum('amount'),
            'thisMonth' => (int) $thisMonth->sum('amount'),
            'totalRecords' => Expense::query()->count(),
            'largestCategory' => $byCategory->first()['category'] ?? null,
            'byCategory' => $byCategory,
        ]);
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $prefix = '/storage/';

        if (str_starts_with($path, $prefix)) {
            Storage::disk('public')->delete(substr($path, strlen($prefix)));
        }
    }
}
