<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\AuditLogResource;
use App\Http\Resources\TenantResource;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Trainer;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Platform-level gym (tenant) management — list every gym on the
 * platform, create/edit one, and move it through its lifecycle
 * (activate/suspend/reactivate). Gated by `platform.admin`, same as
 * PlatformSubscriptionPlanController.
 */
class PlatformGymController extends Controller
{
    private const SORTABLE = [
        'name' => 'name',
        'status' => 'status',
        'createdAt' => 'created_at',
    ];

    public function __construct(private readonly PlatformAuditLogger $auditLogger) {}

    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query()->withCount('users')->with(['owner', 'subscription.plan']);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $sortKey = self::SORTABLE[$request->string('sort_by')->toString()] ?? 'created_at';
        $sortDir = $request->string('sort_dir')->lower()->toString() === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortKey, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(TenantResource::collection($paginator));
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['owner_name', 'owner_email', 'owner_password']);
        $data['status'] ??= 'Trial';
        $data['timezone'] ??= 'UTC';
        $data['currency'] ??= 'USD';

        $tenant = Tenant::create($data);

        if ($request->filled('owner_email')) {
            User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->string('owner_name')->toString(),
                'email' => $request->string('owner_email')->toString(),
                'password' => Hash::make($request->string('owner_password')->toString()),
                'role' => 'Super Admin',
                'status' => 'Active',
            ]);
        }

        $this->auditLogger->log(
            $request->user(),
            'gym.created',
            "Created gym \"{$tenant->name}\".",
            'Tenant',
            $tenant->id,
            $tenant->id,
        );

        return ApiResponse::item(new TenantResource($tenant->load(['owner', 'subscription.plan'])), 201);
    }

    public function show(Request $request, Tenant $gym): JsonResponse
    {
        $gym->load(['owner', 'subscription.plan']);
        $gym->usage_members = Member::query()->where('tenant_id', $gym->id)->count();
        $gym->usage_staff = User::query()->where('tenant_id', $gym->id)->count();
        $gym->usage_trainers = Trainer::query()->where('tenant_id', $gym->id)->count();
        $gym->usage_revenue = Payment::query()->where('tenant_id', $gym->id)->where('status', 'Paid')->sum('amount');

        $activity = AuditLog::query()->where('tenant_id', $gym->id)->latest()->limit(10)->get();

        return ApiResponse::item([
            'gym' => new TenantResource($gym),
            'recentActivity' => AuditLogResource::collection($activity),
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $gym): JsonResponse
    {
        $gym->update($request->validated());

        $this->auditLogger->log(
            $request->user(),
            'gym.updated',
            "Updated gym \"{$gym->name}\".",
            'Tenant',
            $gym->id,
            $gym->id,
        );

        return ApiResponse::item(new TenantResource($gym->load(['owner', 'subscription.plan'])));
    }

    public function activate(Request $request, Tenant $gym): JsonResponse
    {
        return $this->transition($request, $gym, 'Active', 'activated');
    }

    public function suspend(Request $request, Tenant $gym): JsonResponse
    {
        return $this->transition($request, $gym, 'Suspended', 'suspended');
    }

    public function reactivate(Request $request, Tenant $gym): JsonResponse
    {
        return $this->transition($request, $gym, 'Active', 'reactivated');
    }

    private function transition(Request $request, Tenant $gym, string $status, string $verb): JsonResponse
    {
        $gym->update(['status' => $status]);

        $this->auditLogger->log(
            $request->user(),
            "gym.{$verb}",
            "Gym \"{$gym->name}\" was {$verb}.",
            'Tenant',
            $gym->id,
            $gym->id,
        );

        return ApiResponse::item(new TenantResource($gym->load(['owner', 'subscription.plan'])));
    }
}
