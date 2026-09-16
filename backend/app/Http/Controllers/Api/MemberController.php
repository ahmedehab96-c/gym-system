<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetMemberPasswordRequest;
use App\Http\Requests\StoreMemberNoteRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Requests\UpdateMemberStatusRequest;
use App\Http\Resources\MemberResource;
use App\Http\Responses\ApiResponse;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    private const RELATIONS = ['plan', 'trainer', 'notes'];

    private const SORTABLE = [
        'name' => 'name',
        'joinDate' => 'join_date',
        'expiryDate' => 'expiry_date',
        'attendanceRate' => 'attendance_rate',
    ];

    public function __construct(
        private readonly MembershipService $memberships,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Member::query()->with(self::RELATIONS);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($planId = $request->integer('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        $sortKey = self::SORTABLE[$request->string('sort_by')->toString()] ?? 'name';
        $sortDir = $request->string('sort_dir')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortKey, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(MemberResource::collection($paginator));
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['member_id'] ??= $this->generateMemberId();
        $data['join_date'] ??= Carbon::today()->toDateString();
        $data['status'] ??= 'Active';
        $data['attendance_rate'] ??= 0;
        $data['balance_due'] ??= 0;

        $member = DB::transaction(function () use ($data) {
            $planId = $data['plan_id'] ?? null;
            $member = Member::create($data);

            if ($planId) {
                $plan = MembershipPlan::findOrFail($planId);
                $this->memberships->create($member, $plan, $data['start_date'] ?? $member->join_date->toDateString());
                $member->refresh();
            }

            return $member;
        });

        $member->load(self::RELATIONS);
        $this->notifications->newMember($member);

        return ApiResponse::item(new MemberResource($member), 201);
    }

    public function show(Member $member): JsonResponse
    {
        $member->load(self::RELATIONS);

        return ApiResponse::item(new MemberResource($member));
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        $member->update($request->validated());
        $member->load(self::RELATIONS);

        return ApiResponse::item(new MemberResource($member));
    }

    public function updateStatus(UpdateMemberStatusRequest $request, Member $member): JsonResponse
    {
        $member->update(['status' => $request->validated('status')]);
        $member->load(self::RELATIONS);

        return ApiResponse::item(new MemberResource($member));
    }

    public function destroy(Member $member): JsonResponse
    {
        if ($member->payments()->exists() || $member->invoices()->exists()) {
            return ApiResponse::error(
                'This member has payment or invoice records and cannot be deleted.',
                [], 422
            );
        }

        $member->delete();

        return ApiResponse::message('Member deleted successfully.');
    }

    /**
     * Issues (or resets) a member's Flutter Member Mobile App credentials
     * (Phase 25) — staff sets this on the member's behalf, since a member
     * has no prior password to authenticate a self-service reset with.
     */
    public function setPassword(SetMemberPasswordRequest $request, Member $member): JsonResponse
    {
        $member->forceFill(['password' => $request->validated('password')])->save();

        return ApiResponse::message('Member password set successfully.');
    }

    public function storeNote(StoreMemberNoteRequest $request, Member $member): JsonResponse
    {
        $member->notes()->create([
            'author' => $request->user()->name,
            'text' => $request->validated('text'),
        ]);

        $member->load(self::RELATIONS);

        return ApiResponse::item(new MemberResource($member), 201);
    }

    private function generateMemberId(): string
    {
        do {
            $candidate = 'GYM-'.random_int(1000, 9999);
        } while (Member::query()->where('member_id', $candidate)->exists());

        return $candidate;
    }
}
