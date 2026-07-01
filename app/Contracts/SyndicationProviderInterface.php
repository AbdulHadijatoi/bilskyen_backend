<?php

namespace App\Contracts;

use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

interface SyndicationProviderInterface
{
    public function key(): string;

    public function label(): string;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildFeed(Dealer $dealer, Collection $vehicles): Collection;

    public function syncVehicle(Dealer $dealer, Vehicle $vehicle, string $action = 'upsert'): array;
}
