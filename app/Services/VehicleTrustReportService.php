<?php

namespace App\Services;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use Carbon\Carbon;

class VehicleTrustReportService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
    ) {}

    public function isPlatformTrustReportEnabled(): bool
    {
        return filter_var(
            $this->platformSettingService->get('marketplace', 'trust_report_enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForVehicle(Vehicle $vehicle): array
    {
        $priceIntegrity = $this->priceIntegrity($vehicle);
        $inspectionPassed = $this->inspectionPassed($vehicle->last_inspection_result);

        return [
            'inspection_date' => $vehicle->last_inspection_date?->format('Y-m-d'),
            'inspection_result' => $vehicle->last_inspection_result,
            'inspection_odometer' => $vehicle->last_inspection_odometer,
            'inspection_passed' => $inspectionPassed,
            'days_listed' => $this->daysListed($vehicle),
            'price_reduction_percent' => $priceIntegrity['reduction_percent'],
            'original_list_price' => $priceIntegrity['original_price'],
            'has_price_reduction' => $priceIntegrity['has_reduction'],
            'has_recent_price_increase' => $priceIntegrity['has_recent_increase'],
            'total_cost_hint' => $this->totalCostHint($vehicle),
            'trust_badge' => $inspectionPassed && ! $priceIntegrity['has_recent_increase'],
            'has_registry_data' => $vehicle->dmr_fact_vehicle_id !== null
                || $vehicle->last_inspection_date !== null
                || $vehicle->registration !== null,
            'registry' => $this->registrySummary($vehicle),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrySummary(Vehicle $vehicle): array
    {
        $dmr = $vehicle->relationLoaded('dmrFactVehicle') ? $vehicle->dmrFactVehicle : null;

        $registration = $vehicle->registration ?? $dmr?->registrering_nummer;
        $firstRegistration = $vehicle->first_registration_date ?? $dmr?->foerste_registrering_dato;

        $brandName = $dmr?->variant?->model?->brand?->name ?? $vehicle->brand_name;
        $modelName = $dmr?->variant?->model?->name ?? $vehicle->model_name;
        $variantName = $dmr?->variant?->name ?? $vehicle->variant_name;
        $modelYear = $vehicle->model_year ?? $vehicle->model_year_name ?? $dmr?->model_aar;

        return array_filter([
            'registration' => $registration,
            'first_registration_date' => $firstRegistration
                ? Carbon::parse($firstRegistration)->format('Y-m-d')
                : null,
            'brand_name' => $brandName,
            'model_name' => $modelName,
            'variant_name' => $variantName,
            'model_year' => $modelYear,
            'km_driven' => $vehicle->km_driven,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    public function inspectionPassed(?string $result): bool
    {
        if ($result === null || trim($result) === '') {
            return false;
        }

        $normalized = mb_strtolower(trim($result));

        if (str_contains($normalized, 'udbedring') || str_contains($normalized, 'repair')) {
            return false;
        }

        return str_contains($normalized, 'godkendt')
            || str_contains($normalized, 'approved')
            || str_contains($normalized, 'pass');
    }

    private function daysListed(Vehicle $vehicle): ?int
    {
        if ($vehicle->published_at === null) {
            return null;
        }

        return (int) Carbon::parse($vehicle->published_at)->diffInDays(now());
    }

    /**
     * @return array{reduction_percent: ?float, original_price: ?float, has_reduction: bool, has_recent_increase: bool}
     */
    private function priceIntegrity(Vehicle $vehicle): array
    {
        $currentPrice = $vehicle->price !== null ? (float) $vehicle->price : null;

        if (! $vehicle->id) {
            return [
                'reduction_percent' => null,
                'original_price' => $currentPrice,
                'has_reduction' => false,
                'has_recent_increase' => false,
            ];
        }

        $histories = PriceHistory::query()
            ->where('vehicle_id', $vehicle->id)
            ->orderBy('changed_at')
            ->get(['old_price', 'new_price', 'changed_at']);

        if ($histories->isEmpty()) {
            return [
                'reduction_percent' => null,
                'original_price' => $currentPrice,
                'has_reduction' => false,
                'has_recent_increase' => false,
            ];
        }

        $first = $histories->first();
        $originalPrice = (float) ($first->old_price ?? $first->new_price ?? $currentPrice ?? 0);
        $latestHistory = $histories->last();
        $hasRecentIncrease = $latestHistory->changed_at !== null
            && Carbon::parse($latestHistory->changed_at)->gte(now()->subDays(30))
            && (float) $latestHistory->new_price > (float) $latestHistory->old_price;

        $reductionPercent = null;
        $hasReduction = false;
        if ($originalPrice > 0 && $currentPrice !== null && $currentPrice < $originalPrice) {
            $reductionPercent = round((($originalPrice - $currentPrice) / $originalPrice) * 100, 1);
            $hasReduction = $reductionPercent > 0;
        }

        return [
            'reduction_percent' => $reductionPercent,
            'original_price' => $originalPrice > 0 ? $originalPrice : null,
            'has_reduction' => $hasReduction,
            'has_recent_increase' => $hasRecentIncrease,
        ];
    }

    private function totalCostHint(Vehicle $vehicle): ?array
    {
        $price = $vehicle->price !== null ? (float) $vehicle->price : null;
        if ($price === null) {
            return null;
        }

        $annualTax = $vehicle->calculated_ownership_tax !== null
            ? (float) $vehicle->calculated_ownership_tax
            : null;

        return [
            'purchase_price' => $price,
            'annual_road_tax' => $annualTax,
            'first_year_total' => $annualTax !== null ? $price + $annualTax : $price,
        ];
    }

    public function hasRecentPriceDrop(Vehicle $vehicle, int $withinDays = 7): bool
    {
        $latest = PriceHistory::query()
            ->where('vehicle_id', $vehicle->id)
            ->orderByDesc('changed_at')
            ->first();

        if (! $latest || $latest->changed_at === null) {
            return false;
        }

        if (Carbon::parse($latest->changed_at)->lt(now()->subDays($withinDays))) {
            return false;
        }

        return (float) $latest->new_price < (float) $latest->old_price;
    }
}
