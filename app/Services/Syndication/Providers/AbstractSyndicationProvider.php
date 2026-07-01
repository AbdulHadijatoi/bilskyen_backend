<?php

namespace App\Services\Syndication\Providers;

use App\Constants\SyndicationProviderKey;
use App\Contracts\SyndicationProviderInterface;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\Feeds\VehicleFeedBuilderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

abstract class AbstractSyndicationProvider implements SyndicationProviderInterface
{
    public function __construct(
        protected VehicleFeedBuilderService $feedBuilder
    ) {}

    public function syncVehicle(Dealer $dealer, Vehicle $vehicle, string $action = 'upsert'): array
    {
        $vehicles = $this->feedBuilder->publishedVehiclesForDealer($dealer);
        $this->persistFeed($dealer, $vehicles);

        return [
            'success' => true,
            'external_listing_id' => (string) $vehicle->id,
            'message' => __('messages.syndication.feed_regenerated'),
        ];
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    abstract protected function persistFeed(Dealer $dealer, Collection $vehicles): void;

    protected function feedPath(Dealer $dealer, string $extension): string
    {
        return "feeds/dealer-{$dealer->id}/{$this->key()}.{$extension}";
    }

    protected function storeFeed(Dealer $dealer, string $extension, string $contents): void
    {
        Storage::disk('local')->put($this->feedPath($dealer, $extension), $contents);
    }
}
