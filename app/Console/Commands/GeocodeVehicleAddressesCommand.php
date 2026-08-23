<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\DawaGeocodeService;
use Illuminate\Console\Command;

class GeocodeVehicleAddressesCommand extends Command
{
    protected $signature = 'vehicles:geocode-addresses {--limit=100}';

    protected $description = 'Fill missing vehicles.latitude/longitude via DAWA from address + postcode';

    public function handle(DawaGeocodeService $geocoder): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('vehicles', 'latitude')) {
            $this->warn('vehicles.latitude is missing; run migrations first.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $query = Vehicle::query()
            ->withoutGlobalScope('defaultOrder')
            ->whereNull('latitude')
            ->where(function ($q) {
                $q->whereNotNull('address')->where('address', '!=', '')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('postcode')->where('postcode', '!=', '');
                    });
            });

        $updated = 0;
        $query->orderBy('id')->limit($limit)->each(function (Vehicle $vehicle) use ($geocoder, &$updated) {
            $coords = $geocoder->geocode($vehicle->address, $vehicle->postcode);
            if ($coords === null) {
                return;
            }
            $vehicle->forceFill($coords)->save();
            $updated++;
            usleep(150000);
        });

        $this->info("Geocoded {$updated} vehicle(s).");

        return self::SUCCESS;
    }
}
