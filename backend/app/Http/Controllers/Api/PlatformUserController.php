<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlatformUserRequest;
use App\Http\Requests\UpdatePlatformUserStatusRequest;
use App\Http\Resources\PlatformUserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Manages platform-level Super Admin accounts (User::isPlatformAdmin()).
 * Entirely separate from StaffController, which manages a single gym's
 * own staff — a platform user has tenant_id = null and is never visible
 * to, or manageable from, any tenant's own dashboard.
 */
class PlatformUserController extends Controller
{
    public function __construct(private readonly PlatformAuditLogger $auditLogger) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->where('is_platform_admin', true);

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $query->orderBy('name');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(PlatformUserResource::collection($paginator));
    }

    public function store(StorePlatformUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['status'] ??= 'Active';
        $data['role'] = 'Super Admin';

        // tenant_id/is_platform_admin are deliberately not mass-assignable
        // (see User's Fillable attribute) — set directly here, the one
        // legitimate place a platform admin account is ever minted from.
        $platformUser = User::create($data);
        $platformUser->forceFill(['tenant_id' => null, 'is_platform_admin' => true])->save();

        $this->auditLogger->log(
            $request->user(),
            'platform_user.created',
            "Created platform admin account for \"{$platformUser->name}\".",
            'User',
            $platformUser->id,
        );

        return ApiResponse::item(new PlatformUserResource($platformUser), 201);
    }

    public function updateStatus(UpdatePlatformUserStatusRequest $request, User $platformUser): JsonResponse
    {
        abort_unless($platformUser->isPlatformAdmin(), 404);

        if ($platformUser->id === $request->user()->id && $request->validated('status') === 'Inactive') {
            return ApiResponse::error('You cannot deactivate your own account.', [], 422);
        }

        $platformUser->update(['status' => $request->validated('status')]);

        $this->auditLogger->log(
            $request->user(),
            'platform_user.status_updated',
            "Set \"{$platformUser->name}\" to {$platformUser->status}.",
            'User',
            $platformUser->id,
        );

        return ApiResponse::item(new PlatformUserResource($platformUser));
    }
}
