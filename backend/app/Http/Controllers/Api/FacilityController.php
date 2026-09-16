<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Http\Requests\UploadFacilityImageRequest;
use App\Http\Resources\FacilityResource;
use App\Http\Responses\ApiResponse;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return ApiResponse::item(FacilityResource::collection($query->orderBy('name')->get()));
    }

    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Open';

        $facility = Facility::create($data);

        return ApiResponse::item(new FacilityResource($facility), 201);
    }

    public function show(Facility $facility): JsonResponse
    {
        return ApiResponse::item(new FacilityResource($facility));
    }

    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $facility->update($request->validated());

        return ApiResponse::item(new FacilityResource($facility));
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $this->deleteStoredFile($facility->image);
        $facility->delete();

        return ApiResponse::message('Facility deleted successfully.');
    }

    public function uploadImage(UploadFacilityImageRequest $request, Facility $facility): JsonResponse
    {
        $this->deleteStoredFile($facility->image);

        $path = $request->file('image')->store('facilities', 'public');
        $facility->update(['image' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new FacilityResource($facility));
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
