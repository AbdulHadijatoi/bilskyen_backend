<?php

namespace App\Services\Reputation;

use App\Models\Dealer;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;

class GoogleReviewService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function dealerReviewSummary(Dealer $dealer): array
    {
        $summary = [
            'review_url' => $dealer->google_review_url,
            'rating' => null,
            'review_count' => null,
        ];

        if (! $dealer->google_place_id) {
            return $summary;
        }

        $apiKey = $this->platformSettingService->get('reputation', 'google_places_api_key');
        if (! $apiKey) {
            return $summary;
        }

        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $dealer->google_place_id,
                'fields' => 'rating,user_ratings_total,url',
                'key' => $apiKey,
            ]);

            $result = $response->json('result');
            if (is_array($result)) {
                $summary['rating'] = $result['rating'] ?? null;
                $summary['review_count'] = $result['user_ratings_total'] ?? null;
                if (empty($summary['review_url']) && ! empty($result['url'])) {
                    $summary['review_url'] = $result['url'];
                }
            }
        } catch (\Throwable) {
            // Optional enrichment; ignore failures.
        }

        return $summary;
    }
}
