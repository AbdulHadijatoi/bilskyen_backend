<?php

namespace App\Services\Syndication\Providers;

use App\Constants\SyndicationProviderKey;
use App\Models\Dealer;
use Illuminate\Support\Collection;

class GenericXmlSyndicationProvider extends AbstractSyndicationProvider
{
    public function key(): string
    {
        return SyndicationProviderKey::GENERIC_XML;
    }

    public function label(): string
    {
        return __('messages.syndication.providers.generic_xml.label');
    }

    public function buildFeed(Dealer $dealer, Collection $vehicles): Collection
    {
        return $vehicles->map(fn ($v) => $this->feedBuilder->mapVehicle($v));
    }

    protected function persistFeed(Dealer $dealer, Collection $vehicles): void
    {
        $this->storeFeed($dealer, 'xml', $this->feedBuilder->toXml($dealer));
    }
}
