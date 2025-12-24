<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create('da_DK');
            
            // Danish cities with realistic coordinates
            $danishCities = [
                ['city' => 'Copenhagen', 'postcode' => '1000', 'region' => 'Hovedstaden', 'latitude' => 55.6761, 'longitude' => 12.5683],
                ['city' => 'Aarhus', 'postcode' => '8000', 'region' => 'Midtjylland', 'latitude' => 56.1629, 'longitude' => 10.2039],
                ['city' => 'Odense', 'postcode' => '5000', 'region' => 'Syddanmark', 'latitude' => 55.4038, 'longitude' => 10.4024],
                ['city' => 'Aalborg', 'postcode' => '9000', 'region' => 'Nordjylland', 'latitude' => 57.0488, 'longitude' => 9.9217],
                ['city' => 'Esbjerg', 'postcode' => '6700', 'region' => 'Syddanmark', 'latitude' => 55.4869, 'longitude' => 8.4513],
                ['city' => 'Randers', 'postcode' => '8900', 'region' => 'Midtjylland', 'latitude' => 56.4603, 'longitude' => 10.0364],
                ['city' => 'Kolding', 'postcode' => '6000', 'region' => 'Syddanmark', 'latitude' => 55.4904, 'longitude' => 9.4721],
                ['city' => 'Horsens', 'postcode' => '8700', 'region' => 'Midtjylland', 'latitude' => 55.8607, 'longitude' => 9.8500],
            ];

            foreach ($danishCities as $city) {
                Location::firstOrCreate(
                    ['postcode' => $city['postcode']],
                    [
                        'city' => $city['city'],
                        'region' => $city['region'],
                        'country_code' => 'DK',
                        'latitude' => $city['latitude'],
                        'longitude' => $city['longitude'],
                    ]
                );
            }
        });
    }
}

