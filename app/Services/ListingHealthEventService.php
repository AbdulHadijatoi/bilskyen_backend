<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\ListingHealthEvent;
use App\Models\Vehicle;
use Carbon\Carbon;

class ListingHealthEventService
{
    private const MEASURE_AFTER_DAYS = 7;

    public function __construct(
        private ListingHealthService $listingHealthService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function captureMetrics(Vehicle $vehicle): array
    {
        $health = $this->listingHealthService->scoreVehicle($vehicle);

        return [
            'score' => $health['score'],
            'views_30d' => $health['metrics']['views_30d'] ?? 0,
            'enquiries_30d' => $health['metrics']['enquiries_30d'] ?? 0,
            'price' => $vehicle->price !== null ? (float) $vehicle->price : null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $beforeMetrics
     */
    public function recordFix(
        Vehicle $vehicle,
        int $dealerId,
        string $fixType,
        ?string $issueKey,
        ?int $userId,
        ?array $beforeMetrics = null,
    ): ListingHealthEvent {
        return ListingHealthEvent::create([
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealerId,
            'fix_type' => $fixType,
            'issue_key' => $issueKey,
            'before_metrics' => $beforeMetrics ?? $this->captureMetrics($vehicle),
            'fixed_at' => now(),
            'status' => 'pending',
            'changed_by_user_id' => $userId,
        ]);
    }

    public function measurePendingEvents(): int
    {
        $cutoff = now()->subDays(self::MEASURE_AFTER_DAYS);
        $measured = 0;

        ListingHealthEvent::query()
            ->where('status', 'pending')
            ->where('fixed_at', '<=', $cutoff)
            ->with('vehicle')
            ->chunkById(50, function ($events) use (&$measured) {
                foreach ($events as $event) {
                    $vehicle = $event->vehicle;
                    if (! $vehicle) {
                        $event->update(['status' => 'measured', 'measured_at' => now()]);
                        continue;
                    }

                    $event->update([
                        'after_metrics' => $this->captureMetrics($vehicle),
                        'measured_at' => now(),
                        'status' => 'measured',
                    ]);
                    $measured++;
                }
            });

        return $measured;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFixImpactReports(int $dealerId, int $limit = 5): array
    {
        return ListingHealthEvent::query()
            ->where('dealer_id', $dealerId)
            ->whereIn('status', ['measured', 'pending'])
            ->with('vehicle:id,title,slug')
            ->orderByDesc('fixed_at')
            ->limit($limit)
            ->get()
            ->map(fn (ListingHealthEvent $event) => $this->formatImpactReport($event))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function stalePriceVehiclesForDealer(int $dealerId): array
    {
        $publishedId = VehicleListStatus::nameToId('published');
        if ($publishedId === null) {
            return [];
        }

        $vehicles = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->where('list_status_id', $publishedId)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now()->subDays(14))
            ->get();

        $stale = [];

        foreach ($vehicles as $vehicle) {
            $health = $this->listingHealthService->scoreVehicle($vehicle);
            $hasStalePriceIssue = collect($health['issues'] ?? [])
                ->contains(fn (array $issue) => ($issue['key'] ?? '') === 'stale_price');

            if (! $hasStalePriceIssue) {
                continue;
            }

            $stale[] = [
                'vehicle_id' => $vehicle->id,
                'title' => $vehicle->title,
                'registration' => $vehicle->registration,
                'price' => $vehicle->price,
                'days_since_price_change' => $health['metrics']['days_since_price_change'] ?? null,
                'pricing' => $health['pricing'] ?? null,
            ];
        }

        return $stale;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatImpactReport(ListingHealthEvent $event): array
    {
        $before = $event->before_metrics ?? [];
        $after = $event->after_metrics ?? [];

        return [
            'id' => $event->id,
            'vehicle_id' => $event->vehicle_id,
            'title' => $event->vehicle?->title,
            'slug' => $event->vehicle?->slug,
            'fix_type' => $event->fix_type,
            'issue_key' => $event->issue_key,
            'status' => $event->status,
            'fixed_at' => $event->fixed_at?->format('Y-m-d H:i:s'),
            'measured_at' => $event->measured_at?->format('Y-m-d H:i:s'),
            'before' => $before,
            'after' => $after,
            'enquiry_lift' => $event->status === 'measured'
                ? (int) ($after['enquiries_30d'] ?? 0) - (int) ($before['enquiries_30d'] ?? 0)
                : null,
            'views_lift' => $event->status === 'measured'
                ? (int) ($after['views_30d'] ?? 0) - (int) ($before['views_30d'] ?? 0)
                : null,
            'score_lift' => $event->status === 'measured'
                ? (int) ($after['score'] ?? 0) - (int) ($before['score'] ?? 0)
                : null,
            'days_until_measured' => $event->status === 'pending'
                ? max(0, self::MEASURE_AFTER_DAYS - (int) Carbon::parse($event->fixed_at)->diffInDays(now()))
                : null,
        ];
    }
}
