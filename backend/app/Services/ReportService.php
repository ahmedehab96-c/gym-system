<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregate + time-series calculations that back the Reports page. Each
 * report controller action builds a filtered query (search/status/date
 * range) and hands it here to derive the "summary" figures and the
 * "chart" time series from the same filtered rows, so both stay in sync
 * with the table of records returned alongside them.
 */
class ReportService
{
    private const GROUPS = ['daily', 'weekly', 'monthly', 'yearly'];

    /**
     * Buckets rows of a (already filtered) query into period buckets
     * between $from and $to, aggregating $valueColumn with $aggregate
     * ('sum' or 'count') per bucket. Missing periods are filled with 0.
     */
    public function series(Builder $query, string $dateColumn, ?string $valueColumn, string $aggregate, string $group, string $from, string $to): array
    {
        $group = in_array($group, self::GROUPS, true) ? $group : 'monthly';

        $columns = array_values(array_unique(array_filter([$dateColumn, $valueColumn])));
        $rows = $query->get($columns);

        $grouped = $rows->groupBy(fn ($row) => $this->bucketKey(Carbon::parse($row->{$dateColumn}), $group));

        $start = $this->alignToBucketStart(Carbon::parse($from), $group);
        $end = $this->alignToBucketStart(Carbon::parse($to), $group);

        $buckets = [];
        $cursor = $start->copy();
        $guard = 0;

        while ($cursor->lte($end) && $guard < 400) {
            $key = $this->bucketKey($cursor, $group);
            $bucketRows = $grouped->get($key, collect());

            $buckets[] = [
                'period' => $key,
                'label' => $this->bucketLabel($cursor, $group),
                'value' => $aggregate === 'count' ? $bucketRows->count() : (int) $bucketRows->sum($valueColumn),
            ];

            $cursor = $this->advance($cursor, $group);
            $guard++;
        }

        return $buckets;
    }

    public function counts(Builder $query, string $column, array $values): array
    {
        return collect($values)
            ->map(fn (string $value) => [$value => (clone $query)->where($column, $value)->count()])
            ->collapse()
            ->all();
    }

    public function groupedSums(Builder $query, string $groupColumn, string $sumColumn): Collection
    {
        return (clone $query)->get([$groupColumn, $sumColumn])
            ->groupBy($groupColumn)
            ->map(fn (Collection $rows, string $key) => [
                $groupColumn => $key,
                'amount' => (int) $rows->sum($sumColumn),
            ])
            ->values();
    }

    private function bucketKey(Carbon $date, string $group): string
    {
        return match ($group) {
            'daily' => $date->toDateString(),
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'yearly' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    private function bucketLabel(Carbon $date, string $group): string
    {
        return match ($group) {
            'daily' => $date->format('M j'),
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('M j'),
            'yearly' => $date->format('Y'),
            default => $date->format('M Y'),
        };
    }

    private function alignToBucketStart(Carbon $date, string $group): Carbon
    {
        return match ($group) {
            'daily' => $date->copy()->startOfDay(),
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'yearly' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfMonth(),
        };
    }

    private function advance(Carbon $date, string $group): Carbon
    {
        return match ($group) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'yearly' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };
    }
}
