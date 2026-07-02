<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ListingHealthService
{
    public function __construct(
        private MarketPricingService $marketPricingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function scoreVehicle(Vehicle $vehicle, ?int $maxImages = 20): array
    {
        $issues = [];
        $score = 100;

        $imageCount = $vehicle->relationLoaded('images')
            ? $vehicle->images->count()
            : $vehicle->images()->count();

        $minPhotos = 5;
        if ($imageCount < $minPhotos) {
            $needed = $minPhotos - $imageCount;
            $issues[] = [
                'key' => 'add_photos',
                'message' => "Add {$needed} more photo(s)",
                'severity' => 'high',
            ];
            $score -= min(25, $needed * 5);
        } elseif ($maxImages > 0 && $imageCount < (int) floor($maxImages * 0.5)) {
            $issues[] = [
                'key' => 'more_photos',
                'message' => 'Add more photos to stand out',
                'severity' => 'medium',
            ];
            $score -= 10;
        }

        $descriptionLength = mb_strlen(trim((string) ($vehicle->description ?? '')));
        if ($descriptionLength < 80) {
            $issues[] = [
                'key' => 'improve_description',
                'message' => 'Write a fuller description',
                'severity' => 'medium',
            ];
            $score -= 15;
        }

        $daysOnMarket = $vehicle->published_at
            ? (int) Carbon::parse($vehicle->published_at)->diffInDays(now())
            : null;

        if ($daysOnMarket !== null && $daysOnMarket > 45) {
            $issues[] = [
                'key' => 'stale_listing',
                'message' => "Listed for {$daysOnMarket} days — consider refreshing price or photos",
                'severity' => 'medium',
            ];
            $score -= 10;
        }

        $views = ListingViewsLog::query()->where('vehicle_id', $vehicle->id)->count();
        $enquiries = Enquiry::query()->where('vehicle_id', $vehicle->id)->count();
        if ($views >= 20 && $enquiries === 0) {
            $issues[] = [
                'key' => 'low_conversion',
                'message' => 'Many views but no enquiries — review price and photos',
                'severity' => 'high',
            ];
            $score -= 15;
        }

        $pricing = $this->marketPricingService->evaluateVehicle($vehicle);
        if ($pricing && $pricing['label'] === 'above_market') {
            $issues[] = [
                'key' => 'price_above_market',
                'message' => sprintf('Price is %.1f%% above market median', $pricing['diff_percent']),
                'severity' => 'high',
            ];
            $score -= 20;
        }

        if (empty($vehicle->view_3d_url)) {
            $issues[] = [
                'key' => 'add_3d_view',
                'message' => 'Add a 3D view for premium presentation',
                'severity' => 'low',
            ];
            $score -= 5;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->gradeForScore($score),
            'issues' => $issues,
            'metrics' => [
                'image_count' => $imageCount,
                'description_length' => $descriptionLength,
                'days_on_market' => $daysOnMarket,
                'views' => $views,
                'enquiries' => $enquiries,
                'conversion_rate' => $views > 0 ? round(($enquiries / $views) * 100, 1) : null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function vehiclesNeedingAttention(int $dealerId, int $limit = 5): array
    {
        $publishedId = VehicleListStatus::nameToId('published');

        $query = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->withCount('images')
            ->with('images');

        if ($publishedId !== null) {
            $query->where('list_status_id', $publishedId);
        }

        return $query
            ->get()
            ->map(function (Vehicle $vehicle) {
                $health = $this->scoreVehicle($vehicle);

                return array_merge(['vehicle_id' => $vehicle->id, 'title' => $vehicle->title], $health);
            })
            ->filter(fn (array $row) => $row['score'] < 80)
            ->sortBy('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private function gradeForScore(int $score): string
    {
        return match (true) {
            $score >= 85 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'fair',
            default => 'needs_attention',
        };
    }
}
