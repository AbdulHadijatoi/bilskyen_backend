<?php

namespace App\Console\Commands;

use App\Models\GdprDataRequest;
use App\Services\Compliance\GdprComplianceService;
use Illuminate\Console\Command;

class ProcessGdprDataRequests extends Command
{
    protected $signature = 'gdpr:process-requests {--limit=20}';

    protected $description = 'Process pending GDPR data export requests';

    public function handle(GdprComplianceService $service): int
    {
        $requests = GdprDataRequest::where('status', 'pending')
            ->where('type', 'export')
            ->orderBy('requested_at')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($requests as $request) {
            $service->processExport($request);
        }

        $this->info('Processed '.$requests->count().' GDPR export request(s).');

        return self::SUCCESS;
    }
}
