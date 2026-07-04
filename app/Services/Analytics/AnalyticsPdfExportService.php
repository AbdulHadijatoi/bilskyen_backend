<?php

namespace App\Services\Analytics;

use App\Models\Dealer;
use App\Support\AnalyticsDateRange;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class AnalyticsPdfExportService
{
    public function __construct(
        private AnalyticsReportingService $reportingService,
    ) {}

    public function generate(Dealer $dealer, ?string $dateRange = '30d'): string
    {
        [$startDate, $endDate] = AnalyticsDateRange::resolve($dateRange);
        $funnel = $this->reportingService->funnel($dealer->id, $startDate, $endDate, true);
        $stock = $this->reportingService->stockMetrics($dealer->id, $startDate, $endDate);
        $assignees = $this->reportingService->assigneePerformance($dealer->id, $startDate, $endDate);
        $trends = $this->reportingService->dailyTrends($dealer->id, $startDate, $endDate);

        $html = view('reports.dealer-analytics-pdf', [
            'dealerName' => $dealer->owner?->name ?? $dealer->slug ?? 'Dealer',
            'periodLabel' => $this->periodLabel($dateRange, $startDate, $endDate),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'funnel' => $funnel,
            'stock' => $stock,
            'assignees' => $assignees,
            'trends' => $trends,
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function periodLabel(?string $dateRange, ?Carbon $startDate, ?Carbon $endDate): string
    {
        if ($startDate && $endDate) {
            return $startDate->format('Y-m-d').' — '.$endDate->format('Y-m-d');
        }

        return $dateRange ?? '30d';
    }
}
