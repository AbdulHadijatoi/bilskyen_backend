<?php

namespace Database\Seeders;

use App\Models\Dealer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DealerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create('da_DK');
            
            // Danish cities for dealers
            $cities = ['Copenhagen', 'Aarhus', 'Odense', 'Aalborg', 'Esbjerg', 'Randers', 'Kolding', 'Horsens'];
            
            for ($i = 0; $i < 8; $i++) {
                // Generate Danish CVR number (8 digits)
                $cvr = str_pad((string) $faker->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                
                Dealer::firstOrCreate(
                    ['cvr' => $cvr],
                    [
                        'address' => $faker->streetAddress(),
                        'city' => $faker->randomElement($cities),
                        'postcode' => $faker->postcode(),
                        'country_code' => 'DK',
                        'logo_path' => null, // Can be set later if needed
                    ]
                );
            }
        });
    }
}

