<?php

namespace App\Services\Syndication\Providers;

use App\Constants\SyndicationProviderKey;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\Feeds\VehicleFeedBuilderService;
use App\Services\Syndication\MetaVehicleCatalogMapper;
use Illuminate\Support\Collection;

class FacebookCatalogSyndicationProvider extends AbstractSyndicationProvider
{
    public function __construct(
        VehicleFeedBuilderService $feedBuilder,
        private MetaVehicleCatalogMapper $catalogMapper,
    ) {
        parent::__construct($feedBuilder);
    }

    public function key(): string
    {
        return SyndicationProviderKey::FACEBOOK_CATALOG;
    }

    public function label(): string
    {
        return __('messages.syndication.providers.facebook_catalog.label');
    }

    public function buildFeed(Dealer $dealer, Collection $vehicles): Collection
    {
        return $vehicles->map(fn (Vehicle $v) => $this->catalogMapper->toRow($v));
    }

    protected function persistFeed(Dealer $dealer, Collection $vehicles): void
    {
        $vehicles->loadMissing($this->catalogMapper->eagerLoads());
        $this->storeFeed($dealer, 'csv', $this->catalogMapper->toCsv($vehicles));
    }
}
