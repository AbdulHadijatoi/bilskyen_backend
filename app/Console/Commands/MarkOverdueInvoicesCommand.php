<?php

namespace App\Console\Commands;

use App\Services\DealerInvoiceService;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent invoices as overdue after the payment grace period';

    public function handle(DealerInvoiceService $invoiceService): int
    {
        $count = $invoiceService->markOverdueInvoices();
        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
