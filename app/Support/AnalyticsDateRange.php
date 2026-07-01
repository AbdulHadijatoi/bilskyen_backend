<?php

namespace App\Support;

use Carbon\Carbon;

class AnalyticsDateRange
{
    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function resolve(?string $dateRange): array
    {
        $now = Carbon::now();

        return match ($dateRange) {
            '7d' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            '3m' => [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay()],
            '1y' => [$now->copy()->subYear()->startOfDay(), $now->copy()->endOfDay()],
            'all' => [null, null],
            default => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function previousPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $days = max(1, $startDate->diffInDays($endDate) + 1);
        $prevEnd = $startDate->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        return [$prevStart, $prevEnd];
    }

    public static function apply($query, ?Carbon $startDate, ?Carbon $endDate, string $column = 'created_at')
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }

    /**
     * @return list<array{date: string, start: Carbon, end: Carbon}>
     */
    public static function chartPeriods(?Carbon $startDate, ?Carbon $endDate): array
    {
        if (! $startDate || ! $endDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff <= 7) {
            $periods = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $periods[] = [
                    'date' => $current->format('Y-m-d'),
                    'start' => $current->copy()->startOfDay(),
                    'end' => $current->copy()->endOfDay(),
                ];
                $current->addDay();
            }

            return $periods;
        }

        if ($daysDiff <= 90) {
            $periods = [];
            $current = $startDate->copy()->startOfWeek();
            while ($current <= $endDate) {
                $periods[] = [
                    'date' => $current->format('Y-m-d'),
                    'start' => $current->copy()->startOfWeek(),
                    'end' => $current->copy()->endOfWeek(),
                ];
                $current->addWeek();
            }

            return $periods;
        }

        $periods = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $periods[] = [
                'date' => $current->format('Y-m'),
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
            ];
            $current->addMonth();
        }

        return $periods;
    }
}
