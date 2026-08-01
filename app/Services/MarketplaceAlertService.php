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
        private MailService $mailService,
        private MarketplaceNotificationService $notificationService,
    ) {}

    public function sendPriceDropAlerts(): int
    {
        $sent = 0;

        $favorites = Favorite::query()
            ->with(['vehicle', 'user'])
            ->whereHas('vehicle')
            ->whereHas('user')
            ->get()
            ->unique(fn ($favorite) => $favorite->user_id.'-'.$favorite->vehicle_id);

        foreach ($favorites as $favorite) {
            $vehicle = $favorite->vehicle;
            $user = $favorite->user;
            if (! $vehicle || ! $user) {
                continue;
            }

            $latest = PriceHistory::query()
                ->where('vehicle_id', $vehicle->id)
                ->orderByDesc('changed_at')
                ->first();

            if (! $latest || $latest->changed_at === null) {
                continue;
            }

            if ((float) $latest->new_price >= (float) $latest->old_price) {
                continue;
            }

            if ($latest->changed_at < now()->subDay()) {
                continue;
            }

            $vehicleUrl = route('vehicle.detail', $vehicle);
            $newPrice = (float) $latest->new_price;
            $title = $vehicle->title ?? __('messages.mail.vehicle_fallback', ['id' => $vehicle->id]);

            try {
                if ($user->email) {
                    $this->mailService->sendMailable(
                        $user->email,
                        new PriceDropAlertMail(
                            vehicleTitle: $title,
                            vehicleUrl: $vehicleUrl,
                            newPrice: $newPrice,
                            currency: 'DKK',
                        ),
                        ['mail_type' => 'price_drop_alert', 'vehicle_id' => $vehicle->id, 'user_id' => $user->id],
                        false
                    );
                }

                $this->notificationService->notify(
                    $user,
                    'price_drop',
                    __('messages.notifications.price_drop_title'),
                    __('messages.notifications.price_drop_message', ['title' => $title]),
                    $vehicleUrl,
                    $vehicle->id,
                    ['new_price' => $newPrice],
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
            ->whereHas('user')
            ->chunkById(100, function ($searches) use (&$sent, $since) {
                foreach ($searches as $search) {
                    $user = $search->user;
                    if (! $user) {
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

                    $vehiclesUrl = route('vehicles');

                    try {
                        if ($user->email) {
                            $this->mailService->sendMailable(
                                $user->email,
                                new SavedSearchMatchMail(
                                    matchCount: $matchCount,
                                    vehiclesUrl: $vehiclesUrl,
                                ),
                                ['mail_type' => 'saved_search_match', 'saved_search_id' => $search->id],
                                false
                            );
                        }

                        $this->notificationService->notify(
                            $user,
                            'saved_search',
                            __('messages.notifications.saved_search_title'),
                            __('messages.notifications.saved_search_message', ['count' => $matchCount]),
                            $vehiclesUrl,
                            null,
                            ['saved_search_id' => $search->id, 'match_count' => $matchCount],
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
