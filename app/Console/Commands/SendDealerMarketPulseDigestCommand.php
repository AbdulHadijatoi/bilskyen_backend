<?php

namespace App\Console\Commands;

use App\Mail\DealerMarketPulseDigestMail;
use App\Models\Dealer;
use App\Services\MailService;
use App\Services\MarketPulseService;
use Illuminate\Console\Command;

class SendDealerMarketPulseDigestCommand extends Command
{
    protected $signature = 'dealers:market-pulse-digest';

    protected $description = 'Send weekly market pulse digest emails to active dealers';

    public function handle(MarketPulseService $marketPulseService, MailService $mailService): int
    {
        $sent = 0;

        Dealer::query()
            ->with('owner')
            ->whereHas('owner', fn ($q) => $q->whereNotNull('email'))
            ->chunkById(50, function ($dealers) use ($marketPulseService, $mailService, &$sent) {
                foreach ($dealers as $dealer) {
                    $email = $dealer->owner?->email;
                    if (! $email) {
                        continue;
                    }

                    $pulse = $marketPulseService->compareDealer($dealer->id);
                    $summaries = collect($pulse['comparisons'] ?? [])
                        ->pluck('summary')
                        ->filter()
                        ->values()
                        ->all();

                    if ($summaries === []) {
                        continue;
                    }

                    try {
                        $mailService->sendMailable(
                            $email,
                            new DealerMarketPulseDigestMail($dealer->name ?? 'Dealer', $summaries),
                            ['mail_type' => 'dealer_market_pulse_digest', 'dealer_id' => $dealer->id],
                            false
                        );
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send dealer market pulse digest', [
                            'dealer_id' => $dealer->id,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Sent {$sent} market pulse digest(s).");

        return self::SUCCESS;
    }
}
