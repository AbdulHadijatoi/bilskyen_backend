<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Mail\PriceDropAlertMail;
use App\Mail\SavedSearchMatchMail;
use App\Models\Favorite;
use App\Models\PriceHistory;
use App\Models\SavedSearch;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class MarketplaceAlertService
{
    public function __construct(
        private MailService $mailService
    ) {}

    public function sendPriceDropAlerts(): int
    {
        $sent = 0;

        $favorites = Favorite::query()
            ->with(['vehicle', 'user'])
            ->whereHas('vehicle')
            ->whereHas('user', fn ($q) => $q->whereNotNull('email'))
            ->get()
            ->unique(fn ($favorite) => $favorite->user_id.'-'.$favorite->vehicle_id);

        foreach ($favorites as $favorite) {
            $vehicle = $favorite->vehicle;
            $user = $favorite->user;
            if (! $vehicle || ! $user?->email) {
                continue;
            }

            $histories = PriceHistory::query()
                ->where('vehicle_id', $vehicle->id)
                ->orderByDesc('created_at')
                ->limit(2)
                ->get();

            if ($histories->count() < 2) {
                continue;
            }

            $latest = (float) $histories[0]->price;
            $previous = (float) $histories[1]->price;
            if ($latest >= $previous) {
                continue;
            }

            if ($histories[0]->created_at < now()->subDay()) {
                continue;
            }

            try {
                $this->mailService->sendMailable(
                    $user->email,
                    new PriceDropAlertMail(
                        vehicleTitle: $vehicle->title ?? ('Vehicle #'.$vehicle->id),
                        vehicleUrl: url('/vehicles/'.$vehicle->slug),
                        newPrice: $latest,
                        currency: 'DKK',
                    ),
                    ['mail_type' => 'price_drop_alert', 'vehicle_id' => $vehicle->id, 'user_id' => $user->id],
                    false
                );
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Price drop alert failed', ['vehicle_id' => $vehicle->id, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    public function sendSavedSearchAlerts(): int
    {
        $sent = 0;
        $since = now()->subDay();

        SavedSearch::query()
            ->with('user')
            ->whereHas('user', fn ($q) => $q->whereNotNull('email'))
            ->chunkById(100, function ($searches) use (&$sent, $since) {
                foreach ($searches as $search) {
                    $user = $search->user;
                    if (! $user?->email) {
                        continue;
                    }

                    $filters = is_array($search->filters) ? $search->filters : [];
                    $query = Vehicle::query()
                        ->where('list_status_id', VehicleListStatus::PUBLISHED)
                        ->where('created_at', '>=', $since);

                    if (! empty($filters['brand_id'])) {
                        $query->where('brand_id', (int) $filters['brand_id']);
                    }
                    if (! empty($filters['model_id'])) {
                        $query->where('model_id', (int) $filters['model_id']);
                    }
                    if (! empty($filters['price_min'])) {
                        $query->where('price', '>=', (float) $filters['price_min']);
                    }
                    if (! empty($filters['price_max'])) {
                        $query->where('price', '<=', (float) $filters['price_max']);
                    }

                    $matchCount = $query->count();
                    if ($matchCount === 0) {
                        continue;
                    }

                    try {
                        $this->mailService->sendMailable(
                            $user->email,
                            new SavedSearchMatchMail(
                                matchCount: $matchCount,
                                vehiclesUrl: url('/vehicles'),
                            ),
                            ['mail_type' => 'saved_search_match', 'saved_search_id' => $search->id],
                            false
                        );
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning('Saved search alert failed', ['search_id' => $search->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        return $sent;
    }
}
