<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\GymClassResource;
use App\Http\Responses\ApiResponse;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Member-facing class browsing/booking (Phase 25 §6) — deliberately its
 * own booking implementation rather than reusing
 * App\Http\Controllers\Api\GymClassController::book(), because the
 * trust boundary is different: staff pick which member to book for a
 * class (member_id from the request body), a member may only ever book
 * *themselves* (member_id from the authenticated token, never the
 * request body) — the validation rules themselves are intentionally
 * identical (member status, capacity, duplicate-booking), enforced
 * server-side either way.
 */
class ClassController extends Controller
{
    private const RELATIONS = ['trainer'];

    public function index(Request $request): JsonResponse
    {
        $query = GymClass::query()->with(self::RELATIONS)->withCount('bookings');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($upcomingOnly = $request->boolean('upcoming', true)) {
            $query->where(function ($q) {
                $q->whereNull('date')->orWhere('date', '>=', now()->toDateString());
            });
        }

        $query->whereIn('status', ['Scheduled', 'Full'])->orderBy('date')->orderBy('start_time');

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(GymClassResource::collection($paginator));
    }

    public function show(GymClass $class): JsonResponse
    {
        $class->load(self::RELATIONS)->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class));
    }

    public function myBookings(Request $request): JsonResponse
    {
        $query = GymClass::query()
            ->with(self::RELATIONS)
            ->withCount('bookings')
            ->whereHas('bookings', fn ($q) => $q->where('member_id', $request->user()->id))
            ->orderBy('date')
            ->orderBy('start_time');

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(GymClassResource::collection($paginator));
    }

    public function book(Request $request, GymClass $class): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        $error = DB::transaction(function () use ($class, $member) {
            $class = GymClass::query()->lockForUpdate()->findOrFail($class->id);

            if (in_array($class->status, ['Cancelled', 'Completed'], true)) {
                return "This class is {$class->status} and cannot accept bookings.";
            }

            if ($member->status !== 'Active') {
                return "Your membership is {$member->status} and cannot book classes.";
            }

            if ($class->bookings()->where('member_id', $member->id)->exists()) {
                return 'You are already booked into this class.';
            }

            if ($class->bookings()->count() >= $class->capacity) {
                return 'This class is full.';
            }

            $class->bookings()->create(['member_id' => $member->id, 'booked_at' => now()]);

            if ($class->bookings()->count() >= $class->capacity && $class->status === 'Scheduled') {
                $class->update(['status' => 'Full']);
            }

            return null;
        });

        if ($error) {
            return ApiResponse::error($error, [], 422);
        }

        $class->refresh()->load(self::RELATIONS)->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class));
    }

    public function cancel(Request $request, GymClass $class): JsonResponse
    {
        $deleted = $class->bookings()->where('member_id', $request->user()->id)->delete();

        if ($deleted && $class->status === 'Full') {
            $class->update(['status' => 'Scheduled']);
        }

        $class->load(self::RELATIONS)->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class));
    }
}
