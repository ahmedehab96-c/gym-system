<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookClassRequest;
use App\Http\Requests\StoreGymClassRequest;
use App\Http\Requests\UpdateGymClassRequest;
use App\Http\Resources\GymClassResource;
use App\Http\Responses\ApiResponse;
use App\Models\GymClass;
use App\Models\Member;
use App\Services\ClassScheduleService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GymClassController extends Controller
{
    private const RELATIONS = ['trainer'];

    public function __construct(
        private readonly ClassScheduleService $schedule,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = GymClass::query()->with(self::RELATIONS)->withCount('bookings');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        if ($day = $request->string('day')->toString()) {
            $query->where('day', $day);
        }

        if ($date = $request->string('date')->toString()) {
            $query->whereDate('date', $date);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $query->orderBy('date')->orderBy('start_time');

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::paginated(GymClassResource::collection($paginator));
    }

    public function store(StoreGymClassRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Scheduled';

        $conflict = $this->schedule->findConflict(
            $data['trainer_id'], $data['date'] ?? null, $data['day'], $data['start_time'], $data['end_time'],
        );

        if ($conflict) {
            return ApiResponse::error(
                "This trainer already has \"{$conflict->name}\" scheduled from {$conflict->start_time} to {$conflict->end_time} on that day.",
                [], 422
            );
        }

        $class = GymClass::create($data);
        $class->load(self::RELATIONS)->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class), 201);
    }

    public function show(GymClass $class): JsonResponse
    {
        $class->load([...self::RELATIONS, 'members'])->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class));
    }

    public function update(UpdateGymClassRequest $request, GymClass $class): JsonResponse
    {
        $data = $request->validated();

        $trainerId = $data['trainer_id'] ?? $class->trainer_id;
        $date = array_key_exists('date', $data) ? $data['date'] : $class->date?->toDateString();
        $day = $data['day'] ?? $class->day;
        $start = $data['start_time'] ?? $class->start_time;
        $end = $data['end_time'] ?? $class->end_time;

        $conflict = $this->schedule->findConflict($trainerId, $date, $day, $start, $end, $class->id);

        if ($conflict) {
            return ApiResponse::error(
                "This trainer already has \"{$conflict->name}\" scheduled from {$conflict->start_time} to {$conflict->end_time} on that day.",
                [], 422
            );
        }

        $previousStatus = $class->status;

        $class->update($data);
        $class->load(self::RELATIONS)->loadCount('bookings');

        if ($class->status === 'Cancelled' && $previousStatus !== 'Cancelled') {
            $this->notifications->classCancellation($class);
        }

        return ApiResponse::item(new GymClassResource($class));
    }

    public function destroy(GymClass $class): JsonResponse
    {
        $class->delete();

        return ApiResponse::message('Class deleted successfully.');
    }

    public function book(BookClassRequest $request, GymClass $class): JsonResponse
    {
        $memberId = $request->validated('member_id');

        $error = DB::transaction(function () use ($class, $memberId) {
            $class = GymClass::query()->lockForUpdate()->findOrFail($class->id);

            if (in_array($class->status, ['Cancelled', 'Completed'], true)) {
                return "This class is {$class->status} and cannot accept bookings.";
            }

            $member = Member::findOrFail($memberId);

            if ($member->status !== 'Active') {
                return "This member's membership is {$member->status} and cannot book classes.";
            }

            if ($class->bookings()->where('member_id', $memberId)->exists()) {
                return 'This member is already booked into this class.';
            }

            if ($class->bookings()->count() >= $class->capacity) {
                return 'This class is full.';
            }

            $class->bookings()->create(['member_id' => $memberId, 'booked_at' => now()]);

            if ($class->bookings()->count() >= $class->capacity && $class->status === 'Scheduled') {
                $class->update(['status' => 'Full']);
            }

            return null;
        });

        if ($error !== null) {
            return ApiResponse::error($error, [], 422);
        }

        $class->refresh()->load([...self::RELATIONS, 'members'])->loadCount('bookings');
        $this->notifications->classReminder($class);

        return ApiResponse::item(new GymClassResource($class), 201);
    }

    public function cancelBooking(GymClass $class, Member $member): JsonResponse
    {
        $deleted = $class->bookings()->where('member_id', $member->id)->delete();

        if ($deleted && $class->status === 'Full') {
            $class->update(['status' => 'Scheduled']);
        }

        $class->load([...self::RELATIONS, 'members'])->loadCount('bookings');

        return ApiResponse::item(new GymClassResource($class));
    }
}
