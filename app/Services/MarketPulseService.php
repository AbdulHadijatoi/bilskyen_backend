<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketPulseService
{
    /**
     * @return array<string, mixed>
     */
    public function compareDealer(int $dealerId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate ??= Carbon::now()->subDays(30);
        $endDate ??= Carbon::now();

        $dealer = $this->dealerMetrics($dealerId, $startDate, $endDate);
        $platform = $this->platformMetrics($startDate, $endDate);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'dealer' => $dealer,
            'platform' => $platform,
            'comparisons' => [
                'enquiry_rate' => $this->compareMetric(
                    $dealer['enquiry_rate'],
                    $platform['enquiry_rate'],
                    'enquiry conversion',
                    true
                ),
                'days_on_market' => $this->compareMetric(
                    $dealer['avg_days_on_market'],
                    $platform['avg_days_on_market'],
                    'days on market',
                    false
                ),
                'photos_per_listing' => $this->compareMetric(
                    $dealer['avg_photos_per_listing'],
                    $platform['avg_photos_per_listing'],
                    'photos per listing',
                    true
                ),
            ],
        ];
    }

    /**
     * @return array<string, float|int|null>
     */
    private function dealerMetrics(int $dealerId, Carbon $startDate, Carbon $endDate): array
    {
        $publishedId = VehicleListStatus::nameToId('published');

        $vehicleIds = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId))
            ->pluck('id');

        if ($vehicleIds->isEmpty()) {
            return $this->emptyMetrics();
        }

        $views = ListingViewsLog::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->count();

        $enquiries = Enquiry::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        return [
            'views' => $views,
            'enquiries' => $enquiries,
            'enquiry_rate' => $views > 0 ? round(($enquiries / $views) * 100, 2) : null,
            'avg_days_on_market' => $this->avgDaysOnMarket($dealerId, $publishedId),
            'avg_photos_per_listing' => $this->avgPhotosPerListing($dealerId, $publishedId),
        ];
    }

    /**
     * @return array<string, float|int|null>
     */
    private function platformMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $publishedId = VehicleListStatus::nameToId('published');

        $vehicleQuery = Vehicle::query()
            ->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId));

        $vehicleIds = $vehicleQuery->pluck('id');
        if ($vehicleIds->isEmpty()) {
            return $this->emptyMetrics();
        }

        $views = ListingViewsLog::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('viewed_at', [$startDate, $endDate])
            ->count();

        $enquiries = Enquiry::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $avgDays = $this->averageDaysOnMarket(
            Vehicle::query()->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId))
        );

        $avgPhotos = DB::table('vehicle_images')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_images.vehicle_id')
            ->when($publishedId, fn ($q) => $q->where('vehicles.list_status_id', $publishedId))
            ->selectRaw('COUNT(vehicle_images.id) / NULLIF(COUNT(DISTINCT vehicles.id), 0) as avg_photos')
            ->value('avg_photos');

        return [
            'views' => $views,
            'enquiries' => $enquiries,
            'enquiry_rate' => $views > 0 ? round(($enquiries / $views) * 100, 2) : null,
            'avg_days_on_market' => $avgDays !== null ? round((float) $avgDays, 1) : null,
            'avg_photos_per_listing' => $avgPhotos !== null ? round((float) $avgPhotos, 1) : null,
        ];
    }

    /**
     * @return array<string, null>
     */
    private function emptyMetrics(): array
    {
        return [
            'views' => 0,
            'enquiries' => 0,
            'enquiry_rate' => null,
            'avg_days_on_market' => null,
            'avg_photos_per_listing' => null,
        ];
    }

    private function avgDaysOnMarket(int $dealerId, ?int $publishedId): ?float
    {
        return $this->averageDaysOnMarket(
            Vehicle::query()
                ->where('dealer_id', $dealerId)
                ->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId))
        );
    }

    private function averageDaysOnMarket($query): ?float
    {
        $dates = (clone $query)
            ->whereNotNull('published_at')
            ->pluck('published_at');

        if ($dates->isEmpty()) {
            return null;
        }

        $total = $dates->sum(fn ($date) => Carbon::parse($date)->diffInDays(now()));

        return round($total / $dates->count(), 1);
    }

    private function avgPhotosPerListing(int $dealerId, ?int $publishedId): ?float
    {
        $avg = DB::table('vehicle_images')
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_images.vehicle_id')
            ->where('vehicles.dealer_id', $dealerId)
            ->when($publishedId, fn ($q) => $q->where('vehicles.list_status_id', $publishedId))
            ->selectRaw('COUNT(vehicle_images.id) / NULLIF(COUNT(DISTINCT vehicles.id), 0) as avg_photos')
            ->value('avg_photos');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function compareMetric(?float $dealerValue, ?float $platformValue, string $label, bool $higherIsBetter): array
    {
        if ($dealerValue === null || $platformValue === null || $platformValue == 0.0) {
            return [
                'label' => $label,
                'dealer_value' => $dealerValue,
                'platform_value' => $platformValue,
                'diff_percent' => null,
                'summary' => null,
            ];
        }

        $diffPercent = round((($dealerValue - $platformValue) / $platformValue) * 100, 1);
        $better = $higherIsBetter ? $diffPercent >= 0 : $diffPercent <= 0;

        $direction = $diffPercent > 0 ? 'above' : ($diffPercent < 0 ? 'below' : 'at');
        $summary = $diffPercent == 0.0
            ? "Your {$label} matches the market average."
            : sprintf(
                'Your %s is %s%.1f%% %s market average.',
                $label,
                $direction === 'at' ? '' : abs($diffPercent),
                $direction === 'at' ? 'at' : $direction,
                $direction === 'at' ? '' : 'the'
            );

        return [
            'label' => $label,
            'dealer_value' => $dealerValue,
            'platform_value' => $platformValue,
            'diff_percent' => $diffPercent,
            'better_than_market' => $better,
            'summary' => trim(str_replace('  ', ' ', $summary)),
        ];
    }
}
