<?php

namespace App\Services;

use App\Models\Vehicle;

/**
 * Shared presentation for public vehicle listing cards (SSR + search API + JS grid).
 */
class VehicleListingPresentationService
{
    public function __construct(
        private VehicleTrustReportService $trustReportService,
        private DealerBadgeService $dealerBadgeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function badgeFields(Vehicle $vehicle): array
    {
        $trust = $this->trustReportService->buildForVehicle($vehicle);

        return [
            'trust_badge' => (bool) ($trust['trust_badge'] ?? false),
            'price_dropped_recently' => $this->trustReportService->hasRecentPriceDrop($vehicle),
            'premium_dealer_badge' => $this->dealerBadgeService->hasPremiumBadge($vehicle->dealer),
            'is_boosted' => (bool) app(ListingBoostService::class)->activeBoostForVehicle($vehicle->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatForApiListing(Vehicle $vehicle): array
    {
        $firstImage = $vehicle->images->first();
        $isDealer = $vehicle->dealer && ! str_starts_with($vehicle->dealer->cvr ?? '', 'INDIVIDUAL-');
        $sellerType = $isDealer ? 'Dealer' : 'Private';

        return array_merge([
            'id' => $vehicle->id,
            'slug' => $vehicle->slug,
            'dealer_id' => $vehicle->dealer_id,
            'title' => $vehicle->title,
            'variant_name' => $vehicle->variant_name,
            'price' => $vehicle->price,
            'thumbnail_url' => $firstImage?->thumbnail_url ?? '/placeholder-vehicle.jpg',
            'km_driven' => $vehicle->km_driven,
            'engine_power_hp' => $vehicle->engine_power_hp,
            'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
            'gear_type_name' => $vehicle->gear_type_name,
            'fuel_type_name' => $vehicle->fuel_type_name,
            'model_year_name' => $vehicle->model_year_name,
            'brand_name' => $vehicle->brand_name,
            'model_name' => $vehicle->model_name,
            'seller_type' => $sellerType,
            'is_dealer' => (bool) $isDealer,
            'is_private' => ! $isDealer,
            'seller_address' => $vehicle->seller_address,
            'seller_postcode' => $vehicle->seller_postcode,
            'user_id' => $vehicle->user_id,
            'sales_type_name' => $vehicle->salesType?->name,
        ], $this->badgeFields($vehicle));
    }
}
