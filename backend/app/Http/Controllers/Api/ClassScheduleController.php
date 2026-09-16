<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GymClassResource;
use App\Http\Responses\ApiResponse;
use App\Models\GymClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ClassScheduleController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = $this->parseDate($request->string('date')->toString()) ?? Carbon::today();

        $classes = $this->filteredQuery($request)->whereDate('date', $date)->get();

        return ApiResponse::item([
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'classes' => GymClassResource::collection($classes),
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        $anchor = $this->parseDate($request->string('date')->toString()) ?? Carbon::today();
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $classes = $this->filteredQuery($request)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy(fn (GymClass $class) => $class->date->toDateString());

        $days = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $classes) {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'day' => $date->format('l'),
                'classes' => GymClassResource::collection($classes->get($date->toDateString(), collect())->values()),
            ];
        });

        return ApiResponse::item([
            'weekStart' => $weekStart->toDateString(),
            'weekEnd' => $weekEnd->toDateString(),
            'days' => $days,
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $anchor = $this->parseMonth($request->string('month')->toString()) ?? Carbon::today()->startOfMonth();
        $monthStart = $anchor->copy()->startOfMonth();
        $monthEnd = $anchor->copy()->endOfMonth();

        $classes = $this->filteredQuery($request)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get()
            ->groupBy(fn (GymClass $class) => $class->date->toDateString());

        $days = collect(range(0, $monthStart->daysInMonth - 1))->map(function (int $offset) use ($monthStart, $classes) {
            $date = $monthStart->copy()->addDays($offset);

            return [
                'date' => $date->toDateString(),
                'day' => $date->format('l'),
                'classes' => GymClassResource::collection($classes->get($date->toDateString(), collect())->values()),
            ];
        });

        return ApiResponse::item([
            'month' => $monthStart->format('Y-m'),
            'days' => $days,
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = GymClass::query()->with('trainer')->withCount('bookings');

        if ($trainerId = $request->integer('trainer_id')) {
            $query->where('trainer_id', $trainerId);
        }

        if ($classId = $request->integer('class_id')) {
            $query->where('id', $classId);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseMonth(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value.'-01')->startOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }
}
