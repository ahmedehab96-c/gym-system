<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Http\Resources\MembershipPlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\MembershipPlan;
use Illuminate\Http\JsonResponse;

class MembershipPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = MembershipPlan::query()
            ->withCount('members')
            ->orderBy('price')
            ->get();

        return ApiResponse::item(MembershipPlanResource::collection($plans));
    }

    public function store(StoreMembershipPlanRequest $request): JsonResponse
    {
        $plan = MembershipPlan::create($request->validated());
        $plan->loadCount('members');

        return ApiResponse::item(new MembershipPlanResource($plan), 201);
    }

    public function show(MembershipPlan $membershipPlan): JsonResponse
    {
        $membershipPlan->loadCount('members');

        return ApiResponse::item(new MembershipPlanResource($membershipPlan));
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan): JsonResponse
    {
        $membershipPlan->update($request->validated());
        $membershipPlan->loadCount('members');

        return ApiResponse::item(new MembershipPlanResource($membershipPlan));
    }

    public function destroy(MembershipPlan $membershipPlan): JsonResponse
    {
        if ($membershipPlan->memberships()->exists()) {
            return ApiResponse::error('This plan has membership records and cannot be deleted.', [], 422);
        }

        $membershipPlan->delete();

        return ApiResponse::message('Membership plan deleted successfully.');
    }
}
