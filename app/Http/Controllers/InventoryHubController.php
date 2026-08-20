<?php

namespace App\Http\Controllers;

use App\Services\InventoryHubService;
use App\Services\SeoService;
use App\Services\VehicleListingPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryHubController extends Controller
{
    public function __construct(
        private InventoryHubService $hubs,
        private SeoService $seoService,
        private VehicleListingPresentationService $listingPresentation,
    ) {}

    public function electric(): View
    {
        $fuelId = $this->hubs->electricFuelTypeId();
        $filters = $fuelId ? ['fuel_type_id' => [$fuelId]] : ['fuel_type_id' => [-1]];
        $count = $fuelId ? $this->hubs->countForFilters($filters) : 0;
        $vehicles = $fuelId ? $this->hubs->listingsForFilters($filters) : collect();
        $indexable = $this->hubs->isIndexable($count);
        $canonical = route('hubs.electric');
        $heading = __('messages.pages.hubs.el_heading');
        $intro = __('messages.pages.hubs.el_intro', ['count' => $count]);

        $seo = $this->seoService->resolveForHub([
            'heading' => $heading,
            'intro' => $intro,
            'count' => $count,
            'indexable' => $indexable,
            'canonical' => $canonical,
            'listing_urls' => $vehicles->map(fn ($vehicle) => [
                'url' => route('vehicle.detail', $vehicle),
                'name' => $vehicle->title,
            ])->all(),
        ]);

        return view('inventory-hub', [
            'heading' => $heading,
            'intro' => $intro,
            'count' => $count,
            'vehicles' => $vehicles,
            'ctaUrl' => $fuelId ? $this->hubs->listingFilterUrl('fuel_type_id', $fuelId) : route('vehicles'),
            'ctaLabel' => __('messages.pages.hubs.el_cta'),
            'emptyLabel' => __('messages.pages.hubs.el_empty'),
            'seo' => $seo,
            'listingPresentation' => $this->listingPresentation,
        ]);
    }

    public function brand(string $brand): View|RedirectResponse
    {
        $canonicalSlug = $this->hubs->canonicalBrandSlug($brand);
        if ($canonicalSlug === null) {
            abort(404);
        }
        if ($canonicalSlug !== $brand) {
            return redirect()->route('hubs.brand', ['brand' => $canonicalSlug], 301);
        }

        $resolved = $this->hubs->resolveBrand($canonicalSlug);
        if (! $resolved) {
            abort(404);
        }

        $filters = ['brand_id' => [$resolved->id]];
        $count = $this->hubs->countForFilters($filters);
        $vehicles = $this->hubs->listingsForFilters($filters);
        $indexable = $this->hubs->isIndexable($count);
        $canonical = route('hubs.brand', ['brand' => $canonicalSlug]);
        $heading = __('messages.pages.hubs.vw_heading');
        $intro = __('messages.pages.hubs.vw_intro', ['count' => $count]);

        $seo = $this->seoService->resolveForHub([
            'heading' => $heading,
            'intro' => $intro,
            'count' => $count,
            'indexable' => $indexable,
            'canonical' => $canonical,
            'listing_urls' => $vehicles->map(fn ($vehicle) => [
                'url' => route('vehicle.detail', $vehicle),
                'name' => $vehicle->title,
            ])->all(),
        ]);

        return view('inventory-hub', [
            'heading' => $heading,
            'intro' => $intro,
            'count' => $count,
            'vehicles' => $vehicles,
            'ctaUrl' => $this->hubs->listingFilterUrl('brand_id', (int) $resolved->id),
            'ctaLabel' => __('messages.pages.hubs.vw_cta'),
            'emptyLabel' => __('messages.pages.hubs.vw_empty'),
            'seo' => $seo,
            'listingPresentation' => $this->listingPresentation,
        ]);
    }
}
