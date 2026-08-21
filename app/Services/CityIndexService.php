<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Location;
use App\Models\MarketplaceCity;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CityIndexService
{
    /**
     * Canonical display names keyed by normalized lookup key.
     *
     * @var array<string, string>
     */
    private const CITY_ALIASES = [
        'copenhagen' => 'København',
        'kobenhavn' => 'København',
        'københavn' => 'København',
        'aarhus' => 'Aarhus',
        'århus' => 'Aarhus',
        'aalborg' => 'Aalborg',
        'ålborg' => 'Aalborg',
        'odense' => 'Odense',
        'esbjerg' => 'Esbjerg',
        'randers' => 'Randers',
        'kolding' => 'Kolding',
        'horsens' => 'Horsens',
        'vejle' => 'Vejle',
        'roskilde' => 'Roskilde',
        'herning' => 'Herning',
        'silkeborg' => 'Silkeborg',
        'næstved' => 'Næstved',
        'naestved' => 'Næstved',
        'frederiksberg' => 'Frederiksberg',
        'viborg' => 'Viborg',
        'køge' => 'Køge',
        'koege' => 'Køge',
        'holstebro' => 'Holstebro',
        'taastrup' => 'Taastrup',
        'tåstrup' => 'Taastrup',
        'slagelse' => 'Slagelse',
        'hillerød' => 'Hillerød',
        'hillerod' => 'Hillerød',
        'helsingør' => 'Helsingør',
        'helsingor' => 'Helsingør',
        'hørsholm' => 'Hørsholm',
        'horsholm' => 'Hørsholm',
        'rødovre' => 'Rødovre',
        'rodovre' => 'Rødovre',
        'glostrup' => 'Glostrup',
        'ballerup' => 'Ballerup',
        'lyngby' => 'Lyngby',
        'kongens lyngby' => 'Lyngby',
        'brøndby' => 'Brøndby',
        'brondby' => 'Brøndby',
        'hvidovre' => 'Hvidovre',
        'albertslund' => 'Albertslund',
        'ishøj' => 'Ishøj',
        'ishoj' => 'Ishøj',
        'vallensbæk' => 'Vallensbæk',
        'vallensbaek' => 'Vallensbæk',
        'herlev' => 'Herlev',
        'gladsaxe' => 'Gladsaxe',
        'gentofte' => 'Gentofte',
        'sønderborg' => 'Sønderborg',
        'sonderborg' => 'Sønderborg',
        'aabenraa' => 'Aabenraa',
        'åbenrå' => 'Aabenraa',
        'nykøbing falster' => 'Nykøbing Falster',
        'nykobing falster' => 'Nykøbing Falster',
        'nykøbing mors' => 'Nykøbing Mors',
        'nykobing mors' => 'Nykøbing Mors',
    ];

    public function normalizeName(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // Strip trailing country noise and postcodes accidentally stored in city.
        $raw = preg_replace('/\s*,?\s*(denmark|danmark|dk)\s*$/iu', '', $raw) ?? $raw;
        $raw = trim($raw);
        if ($raw === '' || mb_strlen($raw) < 2) {
            return null;
        }

        // Reject placeholder / junk values sometimes stored in free-text city fields.
        $invalid = [
            'city', 'by', 'test', 'unknown', 'n/a', 'na', 'null', 'none',
            'address', 'adresse', 'todo', 'xxx', 'string', 'null',
        ];
        if (in_array(mb_strtolower($raw), $invalid, true)) {
            return null;
        }

        $key = $this->normalizationKey($raw);
        if (in_array($key, $invalid, true)) {
            return null;
        }

        return self::CITY_ALIASES[$key] ?? $this->titleCaseCity($raw);
    }

    public function slugForName(string $name): string
    {
        $slug = Str::slug($name, '-', 'da');
        if ($slug === '') {
            $slug = Str::slug($this->normalizationKey($name));
        }

        return $slug !== '' ? $slug : 'by-'.substr(md5($name), 0, 8);
    }

    public function ensureCityFromName(?string $rawName, ?string $region = null): ?MarketplaceCity
    {
        $name = $this->normalizeName($rawName);
        if ($name === null) {
            return null;
        }

        $slug = $this->slugForName($name);
        $key = $this->normalizationKey($name);

        $city = MarketplaceCity::query()
            ->where(function ($q) use ($slug, $key) {
                $q->where('slug', $slug)
                    ->orWhereRaw('LOWER(name) = ?', [$key]);
            })
            ->first();

        if ($city) {
            $aliases = is_array($city->aliases) ? $city->aliases : [];
            $rawTrim = trim((string) $rawName);
            if ($rawTrim !== '' && ! in_array($rawTrim, $aliases, true) && mb_strtolower($rawTrim) !== mb_strtolower($city->name)) {
                $aliases[] = $rawTrim;
                $city->aliases = array_values(array_unique($aliases));
            }
            if ($region && ! $city->region) {
                $city->region = $region;
            }
            if ($city->isDirty()) {
                $city->save();
            }

            return $city;
        }

        $aliases = [];
        $rawTrim = trim((string) $rawName);
        if ($rawTrim !== '' && mb_strtolower($rawTrim) !== mb_strtolower($name)) {
            $aliases[] = $rawTrim;
        }

        // Collect known aliases that map to this canonical name.
        foreach (self::CITY_ALIASES as $aliasKey => $canonical) {
            if ($canonical === $name && $aliasKey !== $key) {
                $aliases[] = $this->titleCaseCity($aliasKey);
            }
        }

        if ($this->indexableCarsCount() >= MarketplaceCity::INDEXABLE_CARS_HARD_STOP) {
            Log::warning('City hard stop: not inserting new marketplace city', [
                'name' => $name,
                'indexable' => MarketplaceCity::INDEXABLE_CARS_HARD_STOP,
            ]);

            return null;
        }

        return MarketplaceCity::create([
            'name' => $name,
            'slug' => $slug,
            'region' => $region,
            'aliases' => array_values(array_unique(array_filter($aliases))),
            'is_active' => true,
        ]);
    }

    public function resolveCityForDealer(Dealer $dealer): ?MarketplaceCity
    {
        if ($dealer->marketplace_city_id) {
            $existing = MarketplaceCity::find($dealer->marketplace_city_id);
            // Keep existing link unless city/postcode changed to something resolvable.
        }

        $city = $this->ensureCityFromName($dealer->city);

        if (! $city && $dealer->postcode) {
            $location = Location::query()
                ->where('postcode', $dealer->postcode)
                ->first();
            if ($location) {
                $city = $this->ensureCityFromName($location->city, $location->region);
            }
        }

        $newId = $city?->id;
        if ((int) $dealer->marketplace_city_id !== (int) $newId) {
            $dealer->marketplace_city_id = $newId;
            $dealer->saveQuietly();
        }

        return $city;
    }

    public function resolveCityForVehicle(Vehicle $vehicle): ?MarketplaceCity
    {
        if ($vehicle->dealer_id) {
            $dealer = $vehicle->relationLoaded('dealer')
                ? $vehicle->dealer
                : $vehicle->dealer()->first();
            if ($dealer) {
                $city = $this->resolveCityForDealer($dealer);
                if ($city) {
                    return $city;
                }
            }
        }

        $postcode = trim((string) ($vehicle->postcode ?? ''));
        if ($postcode !== '') {
            $location = Location::query()->where('postcode', $postcode)->first();
            if ($location) {
                return $this->ensureCityFromName($location->city, $location->region);
            }
        }

        // Private sellers sometimes store city name in address.
        $address = trim((string) ($vehicle->address ?? ''));
        if ($address !== '' && ! str_contains($address, ',')) {
            return $this->ensureCityFromName($address);
        }

        return null;
    }

    public function refreshForCity(MarketplaceCity|int|string $city): ?MarketplaceCity
    {
        if (is_string($city) && ! is_numeric($city)) {
            $city = MarketplaceCity::where('slug', $city)->first();
        } elseif (! $city instanceof MarketplaceCity) {
            $city = MarketplaceCity::find($city);
        }

        if (! $city) {
            return null;
        }

        $vehicleQuery = $this->vehiclesQueryForCity($city);
        $dealerQuery = $this->dealersQueryForCity($city);

        $vehicleStats = (clone $vehicleQuery)
            ->selectRaw('COUNT(*) as cnt, MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $topBrands = (clone $vehicleQuery)
            ->whereNotNull('vehicles.brand_id')
            ->join('dmr_brands', 'dmr_brands.id', '=', 'vehicles.brand_id')
            ->select('vehicles.brand_id', 'dmr_brands.name as brand_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('vehicles.brand_id', 'dmr_brands.name')
            ->orderByDesc('cnt')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->brand_id,
                'name' => $row->brand_name,
                'count' => (int) $row->cnt,
            ])
            ->filter(fn ($row) => ! empty($row['name']))
            ->values()
            ->all();

        $city->published_vehicle_count = (int) ($vehicleStats->cnt ?? 0);
        $city->dealer_count = (clone $dealerQuery)->count();
        $city->min_price = $vehicleStats->min_price !== null ? (float) $vehicleStats->min_price : null;
        $city->max_price = $vehicleStats->max_price !== null ? (float) $vehicleStats->max_price : null;
        $city->top_brands = $topBrands;
        $city->last_computed_at = now();
        $city->is_active = $city->published_vehicle_count > 0 || $city->dealer_count > 0;
        $city->save();

        SeoService::forgetPublicCaches();
        Cache::forget('marketplace_cities:top');
        Cache::forget('marketplace_cities:indexable');

        return $city;
    }

    public function indexableCarsCount(): int
    {
        return (int) MarketplaceCity::query()
            ->where('is_active', true)
            ->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
            ->count();
    }

    public function reindexAll(): int
    {
        $indexable = $this->indexableCarsCount();
        if ($indexable >= MarketplaceCity::INDEXABLE_CARS_WARNING) {
            Log::warning('Indexable city car pages at or above warning threshold', [
                'count' => $indexable,
                'warning' => MarketplaceCity::INDEXABLE_CARS_WARNING,
                'hard_stop' => MarketplaceCity::INDEXABLE_CARS_HARD_STOP,
            ]);
        }

        // Seed from locations catalog.
        $locationCities = Location::query()
            ->select('city', 'region')
            ->whereNotNull('city')
            ->groupBy('city', 'region')
            ->get();

        foreach ($locationCities as $row) {
            $this->ensureCityFromName($row->city, $row->region);
        }

        // Seed / link dealers.
        Dealer::query()
            ->select('id', 'city', 'postcode', 'marketplace_city_id')
            ->orderBy('id')
            ->chunkById(200, function ($dealers) {
                foreach ($dealers as $dealer) {
                    $this->resolveCityForDealer($dealer);
                }
            });

        // Ensure cities referenced only via vehicle postcodes exist.
        $postcodes = Vehicle::query()
            ->withoutGlobalScopes()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('postcode')
            ->where('postcode', '!=', '')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('postcode');

        if ($postcodes->isNotEmpty()) {
            Location::query()
                ->whereIn('postcode', $postcodes)
                ->get(['city', 'region'])
                ->each(fn (Location $loc) => $this->ensureCityFromName($loc->city, $loc->region));
        }

        $count = 0;
        MarketplaceCity::query()->orderBy('id')->chunkById(100, function ($cities) use (&$count) {
            foreach ($cities as $city) {
                $this->refreshForCity($city);
                $count++;
            }
        });

        return $count;
    }

    public function vehiclesQueryForCity(MarketplaceCity $city): Builder
    {
        $names = $city->matchNames();
        $postcodes = $this->postcodesForCity($city);

        return Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->where(function (Builder $q) use ($city, $names, $postcodes) {
                $q->whereHas('dealer', function (Builder $dealer) use ($city, $names) {
                    $dealer->where('marketplace_city_id', $city->id);
                    if ($names !== []) {
                        $dealer->orWhere(function (Builder $inner) use ($names) {
                            foreach ($names as $name) {
                                $inner->orWhereRaw('LOWER(city) = ?', [$name]);
                            }
                        });
                    }
                });

                if ($postcodes !== []) {
                    $q->orWhereIn('postcode', $postcodes);
                }
            });
    }

    public function dealersQueryForCity(MarketplaceCity $city): Builder
    {
        $names = $city->matchNames();

        return Dealer::query()
            ->where(function (Builder $q) use ($city, $names) {
                $q->where('marketplace_city_id', $city->id);
                if ($names !== []) {
                    $q->orWhere(function (Builder $inner) use ($names) {
                        foreach ($names as $name) {
                            $inner->orWhereRaw('LOWER(city) = ?', [$name]);
                        }
                    });
                }
            });
    }

    /**
     * @return list<string>
     */
    public function postcodesForCity(MarketplaceCity $city): array
    {
        $names = $city->matchNames();
        if ($names === []) {
            return [];
        }

        return Location::query()
            ->where(function (Builder $q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhereRaw('LOWER(city) = ?', [$name]);
                }
            })
            ->pluck('postcode')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, MarketplaceCity>
     */
    public function topCities(int $limit = 8): Collection
    {
        return Cache::remember('marketplace_cities:top', 3600, function () use ($limit) {
            return MarketplaceCity::query()
                ->where('is_active', true)
                ->where(function (Builder $q) {
                    $q->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
                        ->orWhere('dealer_count', '>=', MarketplaceCity::MIN_DEALERS_FOR_INDEX);
                })
                ->orderByDesc('published_vehicle_count')
                ->orderByDesc('dealer_count')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * @return Collection<int, MarketplaceCity>
     */
    public function indexableCities(): Collection
    {
        return Cache::remember('marketplace_cities:indexable', 3600, function () {
            return MarketplaceCity::query()
                ->where('is_active', true)
                ->where(function (Builder $q) {
                    $q->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
                        ->orWhere('dealer_count', '>=', MarketplaceCity::MIN_DEALERS_FOR_INDEX);
                })
                ->orderBy('name')
                ->get();
        });
    }

    public function findBySlug(string $slug): ?MarketplaceCity
    {
        return MarketplaceCity::where('slug', $slug)->first();
    }

    private function normalizationKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['æ', 'ø', 'å'], ['ae', 'oe', 'aa'], $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value;
    }

    private function titleCaseCity(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
