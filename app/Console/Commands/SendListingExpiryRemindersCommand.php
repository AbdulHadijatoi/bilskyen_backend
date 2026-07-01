<?php

namespace App\Console\Commands;

use App\Constants\VehicleListStatus;
use App\Mail\ListingExpiryReminderMail;
use App\Models\Vehicle;
use App\Services\MailService;
use Illuminate\Console\Command;

class SendListingExpiryRemindersCommand extends Command
{
    protected $signature = 'listings:send-expiry-reminders';

    protected $description = 'Email sellers and dealers before listings expire';

    public function handle(MailService $mailService): int
    {
        $warningDays = (int) config('marketplace.listing_expiry_warning_days', 7);
        $targetDate = now()->addDays($warningDays)->endOfDay();
        $sent = 0;

        $vehicles = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', $targetDate->toDateString())
            ->with(['dealer.owner', 'user'])
            ->get();

        foreach ($vehicles as $vehicle) {
            $email = $vehicle->dealer?->owner?->email ?? $vehicle->user?->email;
            if (! $email) {
                continue;
            }

            $manageUrl = $vehicle->dealer_id
                ? url('/vehicles/'.$vehicle->slug)
                : url('/seller-dashboard');

            $mailService->sendMailable(
                $email,
                new ListingExpiryReminderMail(
                    vehicleTitle: $vehicle->title ?? ('Vehicle #'.$vehicle->id),
                    daysRemaining: $warningDays,
                    manageUrl: $manageUrl,
                ),
                ['mail_type' => 'listing_expiry_reminder', 'vehicle_id' => $vehicle->id],
                false
            );

            $sent++;
        }

        $this->info("Sent {$sent} listing expiry reminder(s).");

        return self::SUCCESS;
    }
}
