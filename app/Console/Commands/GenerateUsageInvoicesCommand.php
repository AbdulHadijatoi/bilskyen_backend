<?php

namespace App\Console\Commands;

use App\Services\DealerInvoiceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateUsageInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-usage {--month=} {--year=}';

    protected $description = 'Generate draft invoices from pending listing usage charges';

    public function handle(DealerInvoiceService $invoiceService): int
    {
        $timezone = config('marketplace.timezone', 'Europe/Copenhagen');
        $now = now($timezone);

        $year = (int) ($this->option('year') ?: $now->year);
        $month = (int) ($this->option('month') ?: $now->copy()->subMonth()->month);

        $periodStart = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $invoices = $invoiceService->generateMonthlyInvoices($periodStart, $periodEnd);

        $this->info("Generated {$invoices->count()} invoice(s) for {$periodStart->format('Y-m')}.");

        return self::SUCCESS;
    }
}
