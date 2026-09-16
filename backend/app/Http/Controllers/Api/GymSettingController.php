<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGymSettingsRequest;
use App\Http\Requests\UploadGymLogoRequest;
use App\Http\Resources\GymSettingResource;
use App\Http\Responses\ApiResponse;
use App\Models\GymSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * The gym's settings are a single row (a singleton), not a list — there is
 * only ever one active configuration for this gym.
 */
class GymSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::item(new GymSettingResource($this->current()));
    }

    public function update(UpdateGymSettingsRequest $request): JsonResponse
    {
        $setting = $this->current();
        $setting->update($request->validated());

        return ApiResponse::item(new GymSettingResource($setting));
    }

    public function uploadLogo(UploadGymLogoRequest $request): JsonResponse
    {
        $setting = $this->current();
        $this->deleteStoredFile($setting->logo_url);

        $path = $request->file('logo')->store('settings', 'public');
        $setting->update(['logo_url' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new GymSettingResource($setting));
    }

    private function current(): GymSetting
    {
        return GymSetting::query()->first() ?? GymSetting::create(['name' => 'My Gym']);
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
