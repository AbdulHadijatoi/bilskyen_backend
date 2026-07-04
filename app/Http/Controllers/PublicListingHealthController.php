<?php

namespace App\Http\Controllers;

use App\Services\ListingHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicListingHealthController extends Controller
{
    private const MAX_VEHICLES = 5;

    public function __construct(
        private ListingHealthService $listingHealthService,
    ) {}

    /**
     * Prospect inventory audit — score up to 5 vehicles without authentication.
     * Uses snapshot scoring only; no internal listing metrics are exposed.
     */
    public function audit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicles' => 'required|array|min:1|max:'.self::MAX_VEHICLES,
            'vehicles.*.title' => 'nullable|string|max:255',
            'vehicles.*.registration' => 'nullable|string|max:32',
            'vehicles.*.description' => 'nullable|string|max:10000',
            'vehicles.*.image_count' => 'nullable|integer|min:0|max:100',
            'vehicles.*.equipment_count' => 'nullable|integer|min:0|max:200',
            'vehicles.*.price' => 'nullable|numeric|min:0',
        ]);

        $results = [];
        $scores = [];

        foreach ($data['vehicles'] as $input) {
            $title = trim((string) ($input['title'] ?? ''));
            $registration = trim((string) ($input['registration'] ?? ''));
            $description = trim((string) ($input['description'] ?? ''));

            if ($title === '' && $registration === '' && $description === '') {
                continue;
            }

            $snapshot = [
                'title' => $title ?: null,
                'registration' => $registration ?: null,
                'description' => $description,
                'image_count' => $input['image_count'] ?? 0,
                'equipment_count' => $input['equipment_count'] ?? 0,
                'price' => $input['price'] ?? null,
            ];

            $health = $this->listingHealthService->scoreSnapshot($snapshot);
            $results[] = $health;
            $scores[] = $health['score'];
        }

        if ($scores === []) {
            return $this->error(__('messages.pages.inventory_audit.error_empty'), [], 422);
        }

        $avgScore = (int) round(array_sum($scores) / count($scores));

        return $this->success([
            'items' => $results,
            'portfolio' => [
                'avg_score' => $avgScore,
                'platform_avg_score' => $this->listingHealthService->platformAverageScore(),
                'attention_count' => collect($scores)->filter(fn ($s) => $s < 80)->count(),
                'published_count' => count($scores),
            ],
            'benchmark_message' => $this->benchmarkMessage($avgScore),
        ]);
    }

    private function benchmarkMessage(int $avgScore): string
    {
        if ($avgScore >= 85) {
            return 'Your sample inventory is in excellent shape compared to typical Bilskyen listings.';
        }
        if ($avgScore >= 70) {
            return 'Your listings are healthy, with a few opportunities to capture more enquiries.';
        }

        return 'Several listings need attention — improving photos, descriptions, and pricing could recover lost visibility.';
    }
}
