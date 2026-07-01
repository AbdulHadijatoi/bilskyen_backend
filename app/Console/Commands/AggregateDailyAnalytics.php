<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsAggregationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateDailyAnalytics extends Command
{
    protected $signature = 'analytics:aggregate-daily
                            {--date= : Aggregate a specific date (Y-m-d); defaults to yesterday}
                            {--backfill= : Backfill N days including yesterday}';

    protected $description = 'Roll up daily dealer and platform analytics metrics';

    public function handle(AnalyticsAggregationService $service): int
    {
        if ($days = $this->option('backfill')) {
            $count = $service->backfill((int) $days);
            $this->info("Backfilled {$count} days of analytics.");

            return self::SUCCESS;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $service->aggregateDay($date);
        $this->info('Aggregated analytics for '.$date->toDateString());

        return self::SUCCESS;
    }
}
