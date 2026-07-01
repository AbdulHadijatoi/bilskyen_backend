<?php

namespace App\Services\Syndication\Providers;

use App\Constants\SyndicationProviderKey;
use App\Models\Dealer;
use Illuminate\Support\Collection;

class GenericJsonSyndicationProvider extends AbstractSyndicationProvider
{
    public function key(): string
    {
        return SyndicationProviderKey::GENERIC_JSON;
    }

    public function label(): string
    {
        return __('messages.syndication.providers.generic_json.label');
    }

    public function buildFeed(Dealer $dealer, Collection $vehicles): Collection
    {
        return $vehicles->map(fn ($v) => $this->feedBuilder->mapVehicle($v));
    }

    protected function persistFeed(Dealer $dealer, Collection $vehicles): void
    {
        $this->storeFeed($dealer, 'json', $this->feedBuilder->toJson($dealer));
    }
}
