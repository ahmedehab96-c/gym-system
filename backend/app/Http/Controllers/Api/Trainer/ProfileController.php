<?php

namespace App\Http\Controllers\Api\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\UpdateTrainerProfileRequest;
use App\Http\Requests\UploadTrainerPhotoRequest;
use App\Http\Resources\TrainerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Self-service editing of the CALLER's own Trainer roster row (Phase 26
 * §9) — decoupled from the staff-facing `permission:Trainers,edit` gate
 * (Api\TrainerController), the same way Member's self-profile endpoints
 * are decoupled from staff Member management. Every action resolves the
 * trainer via the authenticated User's linked trainers.user_id, so a
 * trainer can only ever edit their own row, never another trainer's.
 */
class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::item(new TrainerResource($this->currentTrainer($request)));
    }

    public function update(UpdateTrainerProfileRequest $request): JsonResponse
    {
        $trainer = $this->currentTrainer($request);
        $trainer->update($request->validated());

        return ApiResponse::item(new TrainerResource($trainer));
    }

    public function uploadPhoto(UploadTrainerPhotoRequest $request): JsonResponse
    {
        $trainer = $this->currentTrainer($request);
        $this->deleteStoredFile($trainer->photo);

        $path = $request->file('photo')->store('trainers', 'public');
        $trainer->update(['photo' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new TrainerResource($trainer));
    }

    private function currentTrainer(Request $request): Trainer
    {
        return Trainer::query()->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($url, strlen('/storage/')));
    }
}
