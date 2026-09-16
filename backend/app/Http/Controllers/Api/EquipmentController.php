<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Requests\UploadEquipmentImageRequest;
use App\Http\Resources\EquipmentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentController extends Controller
{
    private const SORTABLE = [
        'name' => 'name',
        'purchaseDate' => 'purchase_date',
        'nextMaintenance' => 'next_maintenance',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Equipment::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($condition = $request->string('condition')->toString()) {
            $query->where('condition', $condition);
        }

        if ($location = $request->string('location')->toString()) {
            $query->where('location', $location);
        }

        $sortKey = self::SORTABLE[$request->string('sort_by')->toString()] ?? 'name';
        $sortDir = $request->string('sort_dir')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortKey, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(EquipmentResource::collection($paginator));
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['condition'] ??= 'Excellent';
        $data['status'] ??= 'In Use';

        $equipment = Equipment::create($data);

        return ApiResponse::item(new EquipmentResource($equipment), 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        $equipment->load(['maintenanceRecords' => fn ($q) => $q->orderByDesc('date')]);

        return ApiResponse::item(new EquipmentResource($equipment));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($request->validated());

        return ApiResponse::item(new EquipmentResource($equipment));
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return ApiResponse::message('Equipment deleted successfully.');
    }

    public function uploadImage(UploadEquipmentImageRequest $request, Equipment $equipment): JsonResponse
    {
        $this->deleteStoredFile($equipment->image);

        $path = $request->file('image')->store('equipment', 'public');
        $equipment->update(['image' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new EquipmentResource($equipment));
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
