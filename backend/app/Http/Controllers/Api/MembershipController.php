<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeMembershipPlanRequest;
use App\Http\Requests\StoreMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    private const RELATIONS = ['member', 'plan'];

    public function __construct(
        private readonly MembershipService $memberships,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Membership::query()->with(self::RELATIONS);

        if ($memberId = $request->integer('member_id')) {
            $query->where('member_id', $memberId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->whereHas('member', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $query->latest('start_date');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(MembershipResource::collection($paginator));
    }

    public function store(StoreMembershipRequest $request): JsonResponse
    {
        $data = $request->validated();

        $member = Member::findOrFail($data['member_id']);
        $plan = MembershipPlan::findOrFail($data['plan_id']);

        $hasActiveMembership = Membership::query()
            ->where('member_id', $member->id)
            ->whereIn('status', ['Active', 'Expiring Soon'])
            ->exists();

        if ($hasActiveMembership) {
            return ApiResponse::error(
                'This member already has an active membership. Renew, change plan, or cancel it before creating a new one.',
                [], 422
            );
        }

        $membership = $this->memberships->create($member, $plan, $data['start_date'] ?? null, $data['price'] ?? null);
        $membership->load(self::RELATIONS);
        $this->notifyStatus($membership);

        return ApiResponse::item(new MembershipResource($membership), 201);
    }

    public function show(Membership $membership): JsonResponse
    {
        $membership->load(self::RELATIONS);

        return ApiResponse::item(new MembershipResource($membership));
    }

    public function renew(Membership $membership): JsonResponse
    {
        $membership = $this->memberships->renew($membership)->load(self::RELATIONS);
        $this->notifyStatus($membership);

        return ApiResponse::item(new MembershipResource($membership));
    }

    public function changePlan(ChangeMembershipPlanRequest $request, Membership $membership): JsonResponse
    {
        $plan = MembershipPlan::findOrFail($request->validated('plan_id'));

        $membership = $this->memberships->changePlan($membership, $plan)->load(self::RELATIONS);
        $this->notifyStatus($membership);

        return ApiResponse::item(new MembershipResource($membership));
    }

    public function suspend(Membership $membership): JsonResponse
    {
        $membership = $this->memberships->suspend($membership)->load(self::RELATIONS);

        return ApiResponse::item(new MembershipResource($membership));
    }

    public function cancel(Membership $membership): JsonResponse
    {
        $membership = $this->memberships->cancel($membership)->load(self::RELATIONS);
        $this->notifications->membershipExpired($membership);

        return ApiResponse::item(new MembershipResource($membership));
    }

    private function notifyStatus(Membership $membership): void
    {
        match ($membership->status) {
            'Expiring Soon' => $this->notifications->membershipExpiring($membership),
            'Expired' => $this->notifications->membershipExpired($membership),
            default => null,
        };
    }
}
