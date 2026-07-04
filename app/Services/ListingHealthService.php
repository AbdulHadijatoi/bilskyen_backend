<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Enquiry;
use App\Models\Favorite;
use App\Models\ListingHealthDailyDealer;
use App\Models\ListingHealthScore;
use App\Models\ListingViewsLog;
use App\Models\PriceHistory;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListingHealthService
{
    private const ATTENTION_SCORE_THRESHOLD = 80;

    private const CACHE_MAX_AGE_HOURS = 25;

    public function __construct(
        private MarketPricingService $marketPricingService,
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function scoreVehicle(Vehicle $vehicle, ?int $maxImages = 20, ?Dealer $dealer = null): array
    {
        $issues = [];
        $score = 100;

        $imageCount = $vehicle->relationLoaded('images')
            ? $vehicle->images->count()
            : $vehicle->images()->count();

        $minPhotos = 5;
        if ($imageCount < $minPhotos) {
            $needed = $minPhotos - $imageCount;
            $issues[] = $this->issue('add_photos', "Add {$needed} more photo(s)", 'high');
            $score -= min(25, $needed * 5);
        } elseif ($maxImages > 0 && $imageCount < (int) floor($maxImages * 0.5)) {
            $issues[] = $this->issue('more_photos', 'Add more photos to stand out', 'medium');
            $score -= 10;
        }

        $descriptionLength = mb_strlen(trim((string) ($vehicle->description ?? '')));
        if ($descriptionLength < 80) {
            $issues[] = $this->issue('improve_description', 'Write a fuller description', 'medium');
            $score -= 15;
        }

        $highlightsLength = mb_strlen(trim((string) ($vehicle->highlights ?? '')));
        if ($highlightsLength < 20) {
            $issues[] = $this->issue('add_highlights', 'Add key highlights to attract buyers', 'medium');
            $score -= 8;
        }

        if (empty($vehicle->video_url)) {
            $issues[] = $this->issue('add_video', 'Add a video tour to boost engagement', 'low');
            $score -= 5;
        }

        $metaTitleLength = mb_strlen(trim((string) ($vehicle->meta_title ?? '')));
        $metaDescLength = mb_strlen(trim((string) ($vehicle->meta_description ?? '')));
        if ($metaTitleLength < 10 || $metaDescLength < 40) {
            $issues[] = $this->issue('improve_seo', 'Complete SEO title and description', 'low');
            $score -= 5;
        }

        $daysOnMarket = $vehicle->published_at
            ? (int) Carbon::parse($vehicle->published_at)->diffInDays(now())
            : null;

        if ($daysOnMarket !== null && $daysOnMarket > 45) {
            $issues[] = $this->issue(
                'stale_listing',
                "Listed for {$daysOnMarket} days — consider refreshing price or photos",
                'medium'
            );
            $score -= 10;
        }

        if ($vehicle->expires_at && Carbon::parse($vehicle->expires_at)->lte(now()->addDays(14))) {
            $daysLeft = max(0, (int) now()->diffInDays(Carbon::parse($vehicle->expires_at), false));
            $issues[] = $this->issue(
                'expiring_soon',
                $daysLeft === 0
                    ? 'Listing expires today — renew to stay visible'
                    : "Listing expires in {$daysLeft} day(s)",
                'high'
            );
            $score -= 15;
        }

        $daysSincePriceChange = $this->daysSinceLastPriceChange($vehicle);
        if ($daysSincePriceChange !== null && $daysSincePriceChange >= 14 && $daysOnMarket !== null && $daysOnMarket >= 14) {
            $issues[] = $this->issue(
                'stale_price',
                "No price change in {$daysSincePriceChange} days — market may have moved",
                'medium'
            );
            $score -= 8;
        }

        $views30 = $this->viewCount($vehicle->id, 30);
        $enquiries30 = $this->enquiryCount($vehicle->id, 30);
        $views = $this->viewCount($vehicle->id);
        $enquiries = $this->enquiryCount($vehicle->id);

        if ($views >= 20 && $enquiries === 0) {
            $issues[] = $this->issue(
                'low_conversion',
                'Many views but no enquiries — review price and photos',
                'high'
            );
            $score -= 15;
        }

        if ($views30 >= 10 && $enquiries30 === 0) {
            $issues[] = $this->issue(
                'low_conversion_recent',
                "Likely losing enquiries — {$views30} views, 0 leads in 30 days",
                'high'
            );
            $score -= 10;
        }

        $pricing = $this->marketPricingService->evaluateVehicle($vehicle);
        if ($pricing && $pricing['label'] === 'above_market') {
            $issues[] = $this->issue(
                'price_above_market',
                sprintf('Price is %.1f%% above market median', $pricing['diff_percent']),
                'high'
            );
            $score -= 20;
        }

        if (empty($vehicle->view_3d_url)) {
            $issues[] = $this->issue('add_3d_view', 'Add a 3D view for premium presentation', 'low');
            $score -= 5;
        }

        $equipmentCount = $vehicle->relationLoaded('equipment')
            ? $vehicle->equipment->count()
            : $vehicle->equipment()->count();

        if ($equipmentCount < 3) {
            $issues[] = $this->issue('add_equipment', 'Add equipment details buyers filter on', 'medium');
            $score -= 10;
        }

        $equipmentGap = ['issues' => []];
        if ($this->dealerHasFeature($dealer ?? $vehicle->dealer, 'listing_health_equipment_gap')) {
            $equipmentGap = $this->equipmentGapIssues($vehicle, $equipmentCount);
        }
        foreach ($equipmentGap['issues'] as $gapIssue) {
            $issues[] = $gapIssue;
            $score -= $gapIssue['severity'] === 'high' ? 12 : 8;
        }

        $score = max(0, min(100, $score));

        $favorites30 = Favorite::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $metrics = [
            'image_count' => $imageCount,
            'equipment_count' => $equipmentCount,
            'description_length' => $descriptionLength,
            'highlights_length' => $highlightsLength,
            'days_on_market' => $daysOnMarket,
            'days_since_price_change' => $daysSincePriceChange,
            'views' => $views,
            'views_30d' => $views30,
            'enquiries' => $enquiries,
            'enquiries_30d' => $enquiries30,
            'favorites_30d' => $favorites30,
            'conversion_rate' => $views > 0 ? round(($enquiries / $views) * 100, 1) : null,
        ];

        $health = [
            'score' => $score,
            'grade' => $this->gradeForScore($score),
            'issues' => $this->dedupeIssues($issues),
            'metrics' => $metrics,
            'pricing' => $pricing,
        ];

        $health['priority_score'] = $this->computePriorityScore($health, $vehicle);
        $health['impact_label'] = $this->impactLabel($health);

        return $health;
    }

    /**
     * Score a lightweight vehicle snapshot for public prospect audits.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function scoreSnapshot(array $snapshot): array
    {
        $issues = [];
        $score = 100;

        $imageCount = (int) ($snapshot['image_count'] ?? 0);
        if ($imageCount < 5) {
            $needed = 5 - $imageCount;
            $issues[] = $this->issue('add_photos', "Add {$needed} more photo(s)", 'high');
            $score -= min(25, $needed * 5);
        }

        $descriptionLength = mb_strlen(trim((string) ($snapshot['description'] ?? '')));
        if ($descriptionLength < 80) {
            $issues[] = $this->issue('improve_description', 'Write a fuller description', 'medium');
            $score -= 15;
        }

        $price = isset($snapshot['price']) ? (float) $snapshot['price'] : null;
        $median = isset($snapshot['market_median']) ? (float) $snapshot['market_median'] : null;
        if ($price && $median && $median > 0) {
            $diffPercent = round((($price - $median) / $median) * 100, 1);
            if ($diffPercent >= 5) {
                $issues[] = $this->issue(
                    'price_above_market',
                    sprintf('Price is %.1f%% above market median', $diffPercent),
                    'high'
                );
                $score -= 20;
            }
        }

        $equipmentCount = (int) ($snapshot['equipment_count'] ?? 0);
        if ($equipmentCount < 3) {
            $issues[] = $this->issue('add_equipment', 'Add equipment details buyers filter on', 'medium');
            $score -= 10;
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->gradeForScore($score),
            'issues' => $issues,
            'title' => $snapshot['title'] ?? null,
            'registration' => $snapshot['registration'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttentionInbox(int $dealerId, int $limit = 8, ?Dealer $dealer = null): array
    {
        $qualityItems = $this->vehiclesNeedingAttention($dealerId, $limit, $dealer);
        $operationalItems = $this->operationalAttentionItems($dealerId, $limit, $dealer);

        $merged = collect($qualityItems)
            ->map(fn (array $row) => array_merge($row, ['category' => 'quality']))
            ->concat(
                collect($operationalItems)->map(fn (array $row) => array_merge($row, ['category' => $row['category'] ?? 'incomplete']))
            )
            ->unique('vehicle_id')
            ->sortByDesc('priority_score')
            ->take($limit)
            ->values()
            ->all();

        return [
            'count' => count($merged),
            'items' => $merged,
            'portfolio' => $this->getPortfolioSummary($dealerId),
            'categories' => [
                'quality' => collect($qualityItems)->count(),
                'expiring' => collect($operationalItems)->where('category', 'expiring')->count(),
                'incomplete' => collect($operationalItems)->where('category', 'incomplete')->count(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function vehiclesNeedingAttention(int $dealerId, int $limit = 5, ?Dealer $dealer = null): array
    {
        $cached = $this->cachedAttentionItems($dealerId, $limit, $dealer);
        if ($cached !== null) {
            return $cached;
        }

        return $this->computeAttentionItems($dealerId, $limit, $dealer);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPortfolioSummary(int $dealerId): array
    {
        $publishedId = VehicleListStatus::nameToId('published');
        $today = now()->toDateString();

        $daily = ListingHealthDailyDealer::query()
            ->where('dealer_id', $dealerId)
            ->where('date', $today)
            ->first();

        $weekAgo = ListingHealthDailyDealer::query()
            ->where('dealer_id', $dealerId)
            ->where('date', now()->subDays(7)->toDateString())
            ->first();

        if ($daily) {
            $trend = $weekAgo
                ? $daily->avg_score - $weekAgo->avg_score
                : 0;

            return [
                'avg_score' => $daily->avg_score,
                'platform_avg_score' => $daily->platform_avg_score,
                'attention_count' => $daily->attention_count,
                'published_count' => $daily->published_count,
                'trend_7d' => $trend,
            ];
        }

        $scores = ListingHealthScore::query()
            ->where('dealer_id', $dealerId)
            ->when($publishedId, function ($q) use ($publishedId) {
                $q->whereHas('vehicle', fn ($v) => $v->where('list_status_id', $publishedId));
            })
            ->pluck('score');

        if ($scores->isEmpty()) {
            $liveScores = $this->livePublishedScores($dealerId);
            $avg = $liveScores->isEmpty() ? null : (int) round($liveScores->avg());
            $attention = $liveScores->filter(fn ($s) => $s < self::ATTENTION_SCORE_THRESHOLD)->count();

            return [
                'avg_score' => $avg,
                'platform_avg_score' => $this->platformAverageScoreInternal(),
                'attention_count' => $attention,
                'published_count' => $liveScores->count(),
                'trend_7d' => 0,
            ];
        }

        return [
            'avg_score' => (int) round($scores->avg()),
            'platform_avg_score' => $this->platformAverageScore(),
            'attention_count' => $scores->filter(fn ($s) => $s < self::ATTENTION_SCORE_THRESHOLD)->count(),
            'published_count' => $scores->count(),
            'trend_7d' => 0,
        ];
    }

    public function computeAndStoreDealerScores(int $dealerId): int
    {
        $publishedId = VehicleListStatus::nameToId('published');
        $vehicles = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId))
            ->with(['images', 'equipment'])
            ->get();

        $stored = 0;
        $scores = [];

        foreach ($vehicles as $vehicle) {
            $health = $this->scoreVehicle($vehicle);
            $scores[] = $health['score'];

            ListingHealthScore::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                [
                    'dealer_id' => $dealerId,
                    'score' => $health['score'],
                    'grade' => $health['grade'],
                    'priority_score' => $health['priority_score'],
                    'issues' => $health['issues'],
                    'metrics' => $health['metrics'],
                    'pricing' => $health['pricing'],
                    'computed_at' => now(),
                ]
            );
            $stored++;
        }

        $vehicleIds = $vehicles->pluck('id');
        if ($vehicleIds->isNotEmpty()) {
            ListingHealthScore::query()
                ->where('dealer_id', $dealerId)
                ->whereNotIn('vehicle_id', $vehicleIds)
                ->delete();
        }

        $platformAvg = $this->platformAverageScoreInternal();
        $avgScore = $scores === [] ? 0 : (int) round(array_sum($scores) / count($scores));
        $attentionCount = collect($scores)->filter(fn ($s) => $s < self::ATTENTION_SCORE_THRESHOLD)->count();

        ListingHealthDailyDealer::updateOrCreate(
            ['dealer_id' => $dealerId, 'date' => now()->toDateString()],
            [
                'avg_score' => $avgScore,
                'platform_avg_score' => $platformAvg,
                'attention_count' => $attentionCount,
                'published_count' => count($scores),
            ]
        );

        return $stored;
    }

    public function computeAllDealerScores(): int
    {
        $total = 0;
        Vehicle::query()
            ->whereNotNull('dealer_id')
            ->distinct()
            ->pluck('dealer_id')
            ->each(function (int $dealerId) use (&$total) {
                $total += $this->computeAndStoreDealerScores($dealerId);
            });

        return $total;
    }

    /**
     * @return array<int, string>
     */
    public function attentionSummariesForDealer(int $dealerId, int $limit = 3): array
    {
        return collect($this->vehiclesNeedingAttention($dealerId, $limit))
            ->map(function (array $item) {
                $title = $item['title'] ?? ('Vehicle #'.$item['vehicle_id']);
                $issue = $item['issues'][0]['message'] ?? 'Needs improvement';

                return "{$title}: {$issue} (score {$item['score']})";
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAttentionItem(Vehicle $vehicle, array $health, ?Dealer $dealer = null): array
    {
        return [
            'vehicle_id' => $vehicle->id,
            'title' => $vehicle->title,
            'slug' => $vehicle->slug,
            'score' => $health['score'],
            'grade' => $health['grade'],
            'priority_score' => $health['priority_score'],
            'impact_label' => $health['impact_label'] ?? null,
            'issues' => collect($health['issues'])->map(function (array $issue) use ($vehicle, $health, $dealer) {
                return array_merge($issue, [
                    'actions' => $this->filterActionsForDealer(
                        $this->actionsForIssue($issue['key'], $vehicle, $health, $dealer),
                        $dealer
                    ),
                ]);
            })->values()->all(),
            'metrics' => $health['metrics'],
            'pricing' => $this->dealerHasFeature($dealer, 'pricing_intelligence') ? $health['pricing'] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function operationalAttentionItems(int $dealerId, int $limit, ?Dealer $dealer = null): array
    {
        $vehicles = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->where(function ($q) {
                $q->whereIn('list_status_id', [VehicleListStatus::DRAFT, VehicleListStatus::PENDING_REVIEW])
                    ->orWhere(function ($sub) {
                        $sub->where('list_status_id', VehicleListStatus::PUBLISHED)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()->addDays(14));
                    });
            })
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();

        return $vehicles->map(function (Vehicle $vehicle) {
            $isExpiring = $vehicle->list_status_id === VehicleListStatus::PUBLISHED
                && $vehicle->expires_at
                && Carbon::parse($vehicle->expires_at)->lte(now()->addDays(14));

            $category = $isExpiring ? 'expiring' : 'incomplete';
            $message = match (true) {
                $isExpiring => 'Listing expiring soon — renew to stay visible',
                $vehicle->list_status_id === VehicleListStatus::PENDING_REVIEW => 'Pending review — complete and publish',
                default => 'Draft listing — finish and publish',
            };

            return [
                'vehicle_id' => $vehicle->id,
                'title' => $vehicle->title,
                'slug' => $vehicle->slug,
                'score' => $isExpiring ? 60 : 50,
                'grade' => 'needs_attention',
                'priority_score' => $isExpiring ? 900 : 700,
                'impact_label' => $message,
                'category' => $category,
                'issues' => [[
                    'key' => $category,
                    'message' => $message,
                    'severity' => 'high',
                    'actions' => $this->filterActionsForDealer([[
                        'type' => 'navigate',
                        'target' => 'vehicle_edit',
                        'label' => 'Complete listing',
                    ]], $dealer),
                ]],
                'metrics' => [],
                'pricing' => null,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function cachedAttentionItems(int $dealerId, int $limit, ?Dealer $dealer = null): ?array
    {
        $freshCutoff = now()->subHours(self::CACHE_MAX_AGE_HOURS);

        $rows = ListingHealthScore::query()
            ->where('dealer_id', $dealerId)
            ->where('score', '<', self::ATTENTION_SCORE_THRESHOLD)
            ->where('computed_at', '>=', $freshCutoff)
            ->with('vehicle')
            ->orderByDesc('priority_score')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $anyFresh = ListingHealthScore::query()
                ->where('dealer_id', $dealerId)
                ->where('computed_at', '>=', $freshCutoff)
                ->exists();

            return $anyFresh ? [] : null;
        }

        return $rows->map(function (ListingHealthScore $row) {
            $vehicle = $row->vehicle;

            return [
                'vehicle_id' => $row->vehicle_id,
                'title' => $vehicle?->title,
                'slug' => $vehicle?->slug,
                'score' => $row->score,
                'grade' => $row->grade,
                'priority_score' => $row->priority_score,
                'impact_label' => $this->impactLabelFromStored($row),
                'issues' => collect($row->issues ?? [])->map(function (array $issue) use ($vehicle, $row, $dealer) {
                    return array_merge($issue, [
                        'actions' => $vehicle
                            ? $this->filterActionsForDealer(
                                $this->actionsForIssue($issue['key'], $vehicle, [
                                    'pricing' => $row->pricing,
                                ], $dealer),
                                $dealer
                            )
                            : [],
                    ]);
                })->values()->all(),
                'metrics' => $row->metrics ?? [],
                'pricing' => $this->dealerHasFeature($dealer, 'pricing_intelligence') ? $row->pricing : null,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function computeAttentionItems(int $dealerId, int $limit, ?Dealer $dealer = null): array
    {
        $publishedId = VehicleListStatus::nameToId('published');

        $query = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->with(['images', 'equipment']);

        if ($publishedId !== null) {
            $query->where('list_status_id', $publishedId);
        }

        return $query
            ->get()
            ->map(function (Vehicle $vehicle) use ($dealer) {
                $health = $this->scoreVehicle($vehicle, 20, $dealer);

                return $this->buildAttentionItem($vehicle, $health, $dealer);
            })
            ->filter(fn (array $row) => $row['score'] < self::ATTENTION_SCORE_THRESHOLD)
            ->sortByDesc('priority_score')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function computePriorityScore(array $health, Vehicle $vehicle): int
    {
        $healthGap = max(1, 100 - (int) $health['score']);
        $severityWeight = collect($health['issues'])->sum(function (array $issue) {
            return match ($issue['severity'] ?? 'medium') {
                'high' => 3,
                'medium' => 2,
                default => 1,
            };
        });

        $metrics = $health['metrics'] ?? [];
        $demandSignal = max(1, (int) ($metrics['views_30d'] ?? 0))
            + ((int) ($metrics['enquiries_30d'] ?? 0) * 5)
            + ((int) ($metrics['favorites_30d'] ?? 0) * 2);

        $urgency = 1.0;
        if (! empty($metrics['days_on_market']) && $metrics['days_on_market'] > 45) {
            $urgency += 0.5;
        }
        if ($vehicle->expires_at && Carbon::parse($vehicle->expires_at)->lte(now()->addDays(14))) {
            $urgency += 1.0;
        }
        if (! empty($metrics['days_since_price_change']) && $metrics['days_since_price_change'] >= 14) {
            $urgency += 0.3;
        }

        return (int) round($healthGap * max(1, $demandSignal) * $urgency * max(1, $severityWeight));
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function impactLabel(array $health): ?string
    {
        $metrics = $health['metrics'] ?? [];
        if (($metrics['views_30d'] ?? 0) >= 10 && ($metrics['enquiries_30d'] ?? 0) === 0) {
            return sprintf(
                'Likely losing enquiries — %d views, 0 leads in 30 days',
                $metrics['views_30d']
            );
        }

        foreach ($health['issues'] as $issue) {
            if ($issue['key'] === 'price_above_market' && ! empty($health['pricing']['cohort_count'])) {
                return sprintf(
                    'Priced %.1f%% above %d similar listings',
                    $health['pricing']['diff_percent'],
                    $health['pricing']['cohort_count']
                );
            }
        }

        return $health['issues'][0]['message'] ?? null;
    }

    private function impactLabelFromStored(ListingHealthScore $row): ?string
    {
        $metrics = $row->metrics ?? [];
        if (($metrics['views_30d'] ?? 0) >= 10 && ($metrics['enquiries_30d'] ?? 0) === 0) {
            return sprintf(
                'Likely losing enquiries — %d views, 0 leads in 30 days',
                $metrics['views_30d']
            );
        }

        $pricing = $row->pricing ?? [];
        if (! empty($pricing['diff_percent']) && ($pricing['label'] ?? '') === 'above_market') {
            return sprintf(
                'Priced %.1f%% above %d similar listings',
                $pricing['diff_percent'],
                $pricing['cohort_count'] ?? 0
            );
        }

        return $row->issues[0]['message'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array<int, array<string, mixed>>
     */
    private function actionsForIssue(string $key, Vehicle $vehicle, array $health, ?Dealer $dealer = null): array
    {
        return match ($key) {
            'improve_description', 'low_conversion', 'low_conversion_recent' => [[
                'type' => 'ai',
                'task' => 'vehicle_description',
                'label' => 'Generate description',
            ]],
            'add_highlights' => [[
                'type' => 'ai',
                'task' => 'vehicle_highlights',
                'label' => 'Generate highlights',
            ]],
            'improve_seo' => [[
                'type' => 'ai',
                'task' => 'seo_meta',
                'label' => 'Generate SEO fields',
            ]],
            'price_above_market', 'stale_price', 'stale_listing' => $this->priceIssueActions($health, $dealer),
            'add_photos', 'more_photos' => [[
                'type' => 'navigate',
                'target' => 'vehicle_photos',
                'label' => 'Upload photos',
            ]],
            'add_equipment', 'equipment_gap' => [[
                'type' => 'navigate',
                'target' => 'vehicle_equipment',
                'label' => 'Add equipment',
            ]],
            'add_video', 'add_3d_view' => [[
                'type' => 'navigate',
                'target' => 'vehicle_media',
                'label' => 'Add media',
            ]],
            'expiring_soon' => [[
                'type' => 'navigate',
                'target' => 'vehicle_edit',
                'label' => 'Renew listing',
            ]],
            default => [[
                'type' => 'navigate',
                'target' => 'vehicle_edit',
                'label' => 'Edit listing',
            ]],
        };
    }

    /**
     * @return array{issues: array<int, array<string, mixed>>}
     */
    private function equipmentGapIssues(Vehicle $vehicle, int $equipmentCount): array
    {
        $issues = [];
        $cohortIds = $this->cohortVehicleIds($vehicle);
        if ($cohortIds->count() < 3) {
            return ['issues' => $issues];
        }

        $cohortEquipmentCounts = DB::table('vehicle_equipment')
            ->whereIn('vehicle_id', $cohortIds)
            ->select('vehicle_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('vehicle_id')
            ->pluck('cnt');

        if ($cohortEquipmentCounts->isEmpty()) {
            return ['issues' => $issues];
        }

        $medianEquipment = (int) floor($cohortEquipmentCounts->median());
        if ($medianEquipment >= 5 && $equipmentCount < (int) floor($medianEquipment * 0.6)) {
            $issues[] = $this->issue(
                'equipment_gap',
                "Similar listings include ~{$medianEquipment} equipment items — you have {$equipmentCount}",
                'medium'
            );
        }

        $vehicleEquipmentIds = $vehicle->relationLoaded('equipment')
            ? $vehicle->equipment->pluck('id')
            : $vehicle->equipment()->pluck('equipments.id');

        $popularEquipment = DB::table('vehicle_equipment')
            ->join('equipments', 'equipments.id', '=', 'vehicle_equipment.equipment_id')
            ->whereIn('vehicle_equipment.vehicle_id', $cohortIds)
            ->select('equipments.id', 'equipments.name', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('equipments.id', 'equipments.name')
            ->having('usage_count', '>=', max(2, (int) ceil($cohortIds->count() * 0.5)))
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get();

        $missing = $popularEquipment
            ->filter(fn ($row) => ! $vehicleEquipmentIds->contains((int) $row->id))
            ->take(2);

        if ($missing->isNotEmpty()) {
            $names = $missing->pluck('name')->implode(', ');
            $issues[] = $this->issue(
                'equipment_gap',
                "Competitors often list: {$names}",
                'high'
            );
        }

        return ['issues' => $issues];
    }

    private function cohortVehicleIds(Vehicle $vehicle): Collection
    {
        if (! $vehicle->brand_id || ! $vehicle->model_id) {
            return collect();
        }

        $year = $vehicle->model_year ?? $vehicle->first_registration_year;
        if ($year === null) {
            return collect();
        }

        $publishedId = VehicleListStatus::nameToId('published');
        if ($publishedId === null) {
            return collect();
        }

        return Vehicle::query()
            ->where('list_status_id', $publishedId)
            ->where('brand_id', $vehicle->brand_id)
            ->where('model_id', $vehicle->model_id)
            ->where('id', '!=', $vehicle->id)
            ->where(function ($q) use ($year) {
                $q->where('model_year', (int) $year)
                    ->orWhere('first_registration_year', (int) $year);
            })
            ->limit(50)
            ->pluck('id');
    }

    private function viewCount(int $vehicleId, ?int $days = null): int
    {
        $query = ListingViewsLog::query()->where('vehicle_id', $vehicleId);
        if ($days !== null) {
            $query->where('viewed_at', '>=', now()->subDays($days));
        }

        return $query->count();
    }

    private function enquiryCount(int $vehicleId, ?int $days = null): int
    {
        $query = Enquiry::query()->where('vehicle_id', $vehicleId);
        if ($days !== null) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        return $query->count();
    }

    private function daysSinceLastPriceChange(Vehicle $vehicle): ?int
    {
        $lastChange = PriceHistory::query()
            ->where('vehicle_id', $vehicle->id)
            ->orderByDesc('changed_at')
            ->value('changed_at');

        if ($lastChange) {
            return (int) Carbon::parse($lastChange)->diffInDays(now());
        }

        if ($vehicle->published_at) {
            return (int) Carbon::parse($vehicle->published_at)->diffInDays(now());
        }

        return null;
    }

    public function platformAverageScore(): int
    {
        return $this->platformAverageScoreInternal();
    }

    private function platformAverageScoreInternal(): int
    {
        $avg = ListingHealthScore::query()->avg('score');

        return $avg !== null ? (int) round($avg) : 0;
    }

    /**
     * @return Collection<int, int>
     */
    private function livePublishedScores(int $dealerId): Collection
    {
        $publishedId = VehicleListStatus::nameToId('published');

        return Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->when($publishedId, fn ($q) => $q->where('list_status_id', $publishedId))
            ->with(['images', 'equipment'])
            ->get()
            ->map(fn (Vehicle $v) => $this->scoreVehicle($v)['score']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $issues
     * @return array<int, array<string, mixed>>
     */
    private function dedupeIssues(array $issues): array
    {
        $seen = [];

        return collect($issues)
            ->filter(function (array $issue) use (&$seen) {
                $key = ($issue['key'] ?? '').'|'.($issue['message'] ?? '');
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @return array{key: string, message: string, severity: string}
     */
    private function issue(string $key, string $message, string $severity): array
    {
        return [
            'key' => $key,
            'message' => $message,
            'severity' => $severity,
        ];
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

    private function dealerHasFeature(?Dealer $dealer, string $key, bool $default = false): bool
    {
        if (! $dealer) {
            return $default;
        }

        return $this->subscriptionFeatureService->hasFeature($dealer, $key);
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array<string, mixed>
     */
    public function sanitizeHealthForDealer(array $health, ?Dealer $dealer): array
    {
        if (! $dealer || ! $this->subscriptionFeatureService->hasFeature($dealer, 'pricing_intelligence')) {
            unset($health['pricing']);
        }

        return $health;
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<int, array<string, mixed>>
     */
    private function filterActionsForDealer(array $actions, ?Dealer $dealer): array
    {
        if (! $dealer) {
            return [];
        }

        return collect($actions)->filter(function (array $action) use ($dealer) {
            $type = $action['type'] ?? '';

            if ($type === 'ai') {
                return $this->subscriptionFeatureService->hasFeature($dealer, 'listing_health_ai_fixes');
            }

            if ($type === 'apply_price') {
                return $this->subscriptionFeatureService->hasFeature($dealer, 'listing_health_price_apply');
            }

            return $this->subscriptionFeatureService->hasFeature($dealer, 'listing_health_actions');
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array<int, array<string, mixed>>
     */
    private function priceIssueActions(array $health, ?Dealer $dealer): array
    {
        $actions = [[
            'type' => 'navigate',
            'target' => 'vehicle_price',
            'label' => 'Adjust price',
            'suggested_min' => $health['pricing']['suggested_min'] ?? null,
            'suggested_max' => $health['pricing']['suggested_max'] ?? null,
        ]];

        $medianPrice = $health['pricing']['median_price'] ?? null;
        if ($medianPrice && $dealer && $this->dealerHasFeature($dealer, 'listing_health_price_apply', false)) {
            $actions[] = [
                'type' => 'apply_price',
                'label' => 'Apply suggested price',
                'suggested_price' => (int) round((float) $medianPrice),
            ];
        }

        return $actions;
    }
}
