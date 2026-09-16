<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRecordRequest;
use App\Http\Requests\UpdateMaintenanceRecordRequest;
use App\Http\Resources\MaintenanceRecordResource;
use App\Http\Responses\ApiResponse;
use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Services\MaintenanceService;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MaintenanceRecordController extends Controller
{
    public function __construct(
        private readonly MaintenanceService $maintenance,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->filteredQuery($request)->paginate($this->perPage($request))->appends($request->query());

        return ApiResponse::paginated(MaintenanceRecordResource::collection($paginator));
    }

    public function forEquipment(Request $request, Equipment $equipment): JsonResponse
    {
        $paginator = $this->filteredQuery($request)
            ->where('equipment_id', $equipment->id)
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return ApiResponse::paginated(MaintenanceRecordResource::collection($paginator));
    }

    public function upcoming(): JsonResponse
    {
        $records = MaintenanceRecord::query()
            ->with('equipment')
            ->where('status', 'Upcoming')
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->get();

        return ApiResponse::item(MaintenanceRecordResource::collection($records));
    }

    public function overdue(): JsonResponse
    {
        $records = MaintenanceRecord::query()
            ->with('equipment')
            ->where(function (Builder $query) {
                $query->where('status', 'Overdue')
                    ->orWhere(function (Builder $q) {
                        $q->where('status', 'Upcoming')->whereDate('date', '<', Carbon::today());
                    });
            })
            ->orderBy('date')
            ->get();

        return ApiResponse::item(MaintenanceRecordResource::collection($records));
    }

    public function stats(): JsonResponse
    {
        $today = Carbon::today();

        return ApiResponse::item([
            'total' => MaintenanceRecord::query()->count(),
            'upcoming' => MaintenanceRecord::query()->where('status', 'Upcoming')->whereDate('date', '>=', $today)->count(),
            'overdue' => MaintenanceRecord::query()
                ->where(function (Builder $query) use ($today) {
                    $query->where('status', 'Overdue')
                        ->orWhere(function (Builder $q) use ($today) {
                            $q->where('status', 'Upcoming')->whereDate('date', '<', $today);
                        });
                })->count(),
            'inProgress' => MaintenanceRecord::query()->where('status', 'In Progress')->count(),
            'completed' => MaintenanceRecord::query()->where('status', 'Completed')->count(),
            'totalCost' => (int) MaintenanceRecord::query()->sum('cost'),
        ]);
    }

    public function store(StoreMaintenanceRecordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] ??= 'Upcoming';
        $data['cost'] ??= 0;

        $record = MaintenanceRecord::create($data);
        $this->maintenance->syncEquipment($record);
        $record->load('equipment');
        $this->notifyIfDueOrOverdue($record);

        return ApiResponse::item(new MaintenanceRecordResource($record), 201);
    }

    public function update(UpdateMaintenanceRecordRequest $request, MaintenanceRecord $maintenance): JsonResponse
    {
        $maintenance->update($request->validated());
        $this->maintenance->syncEquipment($maintenance);
        $maintenance->load('equipment');
        $this->notifyIfDueOrOverdue($maintenance);

        return ApiResponse::item(new MaintenanceRecordResource($maintenance));
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = MaintenanceRecord::query()->with('equipment');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('technician', 'like', "%{$search}%");
            });
        }

        if ($equipmentId = $request->integer('equipment_id')) {
            $query->where('equipment_id', $equipmentId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($date = $request->string('date')->toString()) {
            $query->whereDate('date', $date);
        }

        return $query->orderByDesc('date');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->integer('per_page', 15), 1), 100);
    }

    private function notifyIfDueOrOverdue(MaintenanceRecord $record): void
    {
        $today = Carbon::today();
        $date = $record->date ? Carbon::parse($record->date) : null;

        if ($record->status === 'Overdue' || ($record->status === 'Upcoming' && $date?->lt($today))) {
            $this->notifications->maintenanceOverdue($record);
        } elseif ($record->status === 'Upcoming' && $date?->between($today, $today->copy()->addDays(7))) {
            $this->notifications->maintenanceDue($record);
        }
    }
}
