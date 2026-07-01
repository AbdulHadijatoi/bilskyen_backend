<?php

namespace Tests\Unit;

use App\Support\AnalyticsDateRange;
use Carbon\Carbon;
use Tests\TestCase;

class AnalyticsDateRangeTest extends TestCase
{
    public function test_resolve_defaults_to_thirty_days(): void
    {
        Carbon::setTestNow('2026-06-30 12:00:00');

        [$start, $end] = AnalyticsDateRange::resolve(null);

        $this->assertNotNull($start);
        $this->assertNotNull($end);
        $this->assertSame('2026-05-31 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-30 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_resolve_all_time_returns_null_bounds(): void
    {
        [$start, $end] = AnalyticsDateRange::resolve('all');

        $this->assertNull($start);
        $this->assertNull($end);
    }

    public function test_previous_period_matches_window_length(): void
    {
        $start = Carbon::parse('2026-06-01')->startOfDay();
        $end = Carbon::parse('2026-06-30')->endOfDay();

        [$prevStart, $prevEnd] = AnalyticsDateRange::previousPeriod($start, $end);

        $this->assertSame('2026-05-01 00:00:00', $prevStart->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-31 23:59:59', $prevEnd->format('Y-m-d H:i:s'));
    }

    public function test_chart_periods_daily_for_short_ranges(): void
    {
        $start = Carbon::parse('2026-06-28')->startOfDay();
        $end = Carbon::parse('2026-06-30')->endOfDay();

        $periods = AnalyticsDateRange::chartPeriods($start, $end);

        $this->assertCount(3, $periods);
        $this->assertSame('2026-06-28', $periods[0]['date']);
        $this->assertSame('2026-06-30', $periods[2]['date']);
    }
}
