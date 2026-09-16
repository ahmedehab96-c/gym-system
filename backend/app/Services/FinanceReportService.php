<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Support\Carbon;

/**
 * Backend calculations for the Revenue/Financial dashboard: revenue is
 * always the sum of Paid payments; expenses are the sum of Expense rows.
 */
class FinanceReportService
{
    public function overview(): array
    {
        $today = Carbon::today();

        $paid = Payment::query()->where('status', 'Paid');

        $pending = Payment::query()->where('status', 'Pending');
        $refunded = Payment::query()->where('status', 'Refunded');

        $totalRevenue = (int) Payment::query()->where('status', 'Paid')->sum('amount');
        $totalExpenses = (int) Expense::query()->sum('amount');

        return [
            'daily' => (int) (clone $paid)->whereDate('date', $today)->sum('amount'),
            'weekly' => (int) (clone $paid)->whereBetween('date', [
                $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ])->sum('amount'),
            'monthly' => (int) (clone $paid)->whereBetween('date', [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ])->sum('amount'),
            'yearly' => (int) (clone $paid)->whereBetween('date', [
                $today->copy()->startOfYear()->toDateString(),
                $today->copy()->endOfYear()->toDateString(),
            ])->sum('amount'),
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netRevenue' => $totalRevenue - $totalExpenses,
            'pendingPayments' => [
                'count' => (clone $pending)->count(),
                'amount' => (int) (clone $pending)->sum('amount'),
            ],
            'refunds' => [
                'count' => (clone $refunded)->count(),
                'amount' => (int) (clone $refunded)->sum('amount'),
            ],
            'revenueByMethod' => Payment::query()
                ->where('status', 'Paid')
                ->selectRaw('method, SUM(amount) as amount')
                ->groupBy('method')
                ->get()
                ->map(fn ($row) => ['method' => $row->method, 'amount' => (int) $row->amount])
                ->values(),
        ];
    }

    public function revenueOverTime(int $months = 12): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths($months - 1);

        $revenueByMonth = Payment::query()
            ->where('status', 'Paid')
            ->where('date', '>=', $start->toDateString())
            ->get(['date', 'amount'])
            ->groupBy(fn ($payment) => $payment->date->format('Y-m'))
            ->map(fn ($rows) => (int) $rows->sum('amount'));

        $expensesByMonth = Expense::query()
            ->where('date', '>=', $start->toDateString())
            ->get(['date', 'amount'])
            ->groupBy(fn ($expense) => $expense->date->format('Y-m'))
            ->map(fn ($rows) => (int) $rows->sum('amount'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $revenueByMonth, $expensesByMonth) {
                $month = $start->copy()->addMonths($offset);
                $key = $month->format('Y-m');

                return [
                    'month' => $month->format('M'),
                    'revenue' => $revenueByMonth->get($key, 0),
                    'expenses' => $expensesByMonth->get($key, 0),
                ];
            })
            ->all();
    }
}
