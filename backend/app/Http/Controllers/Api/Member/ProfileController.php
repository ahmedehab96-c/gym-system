<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberProfileRequest;
use App\Http\Requests\Member\UploadMemberPhotoRequest;
use App\Http\Resources\MemberResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $member = $request->user()->load(['plan', 'trainer']);

        return ApiResponse::item(new MemberResource($member));
    }

    public function update(UpdateMemberProfileRequest $request): JsonResponse
    {
        $member = $request->user();
        $member->update($request->validated());
        $member->load(['plan', 'trainer']);

        return ApiResponse::item(new MemberResource($member));
    }

    public function uploadPhoto(UploadMemberPhotoRequest $request): JsonResponse
    {
        $member = $request->user();
        $this->deleteStoredFile($member->avatar);

        $path = $request->file('photo')->store('members', 'public');
        $member->update(['avatar' => Storage::disk('public')->url($path)]);
        $member->load(['plan', 'trainer']);

        return ApiResponse::item(new MemberResource($member));
    }

    private function deleteStoredFile(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($url, strlen('/storage/')));
    }
}
