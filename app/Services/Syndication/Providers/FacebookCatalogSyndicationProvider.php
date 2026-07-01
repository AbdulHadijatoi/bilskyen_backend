<?php

namespace App\Services\Syndication\Providers;

use App\Constants\SyndicationProviderKey;
use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class FacebookCatalogSyndicationProvider extends AbstractSyndicationProvider
{
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
        return $vehicles->map(fn (Vehicle $v) => $this->toFacebookRow($v));
    }

    protected function persistFeed(Dealer $dealer, Collection $vehicles): void
    {
        $lines = ['vehicle_id,title,description,price,image[0],url,state_of_vehicle'];
        foreach ($vehicles as $vehicle) {
            $row = $this->toFacebookRow($vehicle);
            $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', array_values($row)));
        }
        $this->storeFeed($dealer, 'csv', implode("\n", $lines));
    }

    /**
     * @return array<string, string>
     */
    private function toFacebookRow(Vehicle $vehicle): array
    {
        $mapped = $this->feedBuilder->mapVehicle($vehicle);

        return [
            'vehicle_id' => (string) $vehicle->id,
            'title' => $vehicle->title ?? '',
            'description' => strip_tags((string) ($vehicle->description ?? '')),
            'price' => number_format((float) $vehicle->price, 2, '.', '').' DKK',
            'image' => $mapped['primary_image'] ?? '',
            'url' => $mapped['url'],
            'state' => 'used',
        ];
    }
}
