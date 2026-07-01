<?php

namespace App\Support;

use App\Constants\LeadStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsLeadMetrics
{
    public static function countWonInPeriod(?int $dealerId, ?Carbon $startDate, ?Carbon $endDate): int
    {
        $query = DB::table('lead_stage_history as h')
            ->join('leads', 'h.lead_id', '=', 'leads.id')
            ->where('h.to_stage_id', LeadStage::WON);

        if ($dealerId !== null) {
            $query->where('leads.dealer_id', $dealerId);
        }

        AnalyticsDateRange::apply($query, $startDate, $endDate, 'h.changed_at');

        return (int) $query->count();
    }
}
