<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Requests\UpdateStaffRoleRequest;
use App\Http\Requests\UpdateStaffStatusRequest;
use App\Http\Requests\UploadStaffPhotoRequest;
use App\Http\Resources\StaffResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    private const SORTABLE = [
        'name' => 'name',
        'email' => 'email',
        'role' => 'role',
        'hireDate' => 'hire_date',
        'lastLogin' => 'last_login_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->toString()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($position = $request->string('position')->toString()) {
            $query->where('position', 'like', "%{$position}%");
        }

        $sortKey = self::SORTABLE[$request->string('sort_by')->toString()] ?? 'name';
        $sortDir = $request->string('sort_dir')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortKey, $sortDir);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(StaffResource::collection($paginator));
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Active';

        $staff = User::create($data);

        return ApiResponse::item(new StaffResource($staff), 201);
    }

    public function show(User $staff): JsonResponse
    {
        return ApiResponse::item(new StaffResource($staff));
    }

    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $staff->update($data);

        return ApiResponse::item(new StaffResource($staff));
    }

    public function destroy(Request $request, User $staff): JsonResponse
    {
        if ($staff->id === $request->user()->id) {
            return ApiResponse::error('You cannot delete your own account.', [], 422);
        }

        $staff->delete();

        return ApiResponse::message('Staff member deleted successfully.');
    }

    public function updateStatus(UpdateStaffStatusRequest $request, User $staff): JsonResponse
    {
        $status = $request->validated('status');

        if ($staff->id === $request->user()->id && $status === 'Inactive') {
            return ApiResponse::error('You cannot deactivate your own account.', [], 422);
        }

        $staff->update(['status' => $status]);

        return ApiResponse::item(new StaffResource($staff));
    }

    public function updateRole(UpdateStaffRoleRequest $request, User $staff): JsonResponse
    {
        if ($staff->id === $request->user()->id) {
            return ApiResponse::error('You cannot change your own role.', [], 422);
        }

        $role = $request->validated('role');

        if ($role === 'Super Admin' && $request->user()->role !== 'Super Admin') {
            return ApiResponse::error('Only a Super Admin can grant the Super Admin role.', [], 403);
        }

        $staff->update(['role' => $role]);

        return ApiResponse::item(new StaffResource($staff));
    }

    public function uploadPhoto(UploadStaffPhotoRequest $request, User $staff): JsonResponse
    {
        $this->deleteStoredFile($staff->photo);

        $path = $request->file('photo')->store('staff', 'public');
        $staff->update(['photo' => Storage::disk('public')->url($path)]);

        return ApiResponse::item(new StaffResource($staff));
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
