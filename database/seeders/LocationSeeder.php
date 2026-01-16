<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        // Truncate the locations table before seeding to avoid duplicates
        DB::table('locations')->truncate();


        DB::transaction(function () {
            $locations = [
                // Hovedstaden (Capital Region)
                ['city' => 'Copenhagen', 'postcode' => '1000', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.6761, 'longitude' => 12.5683],
                ['city' => 'Copenhagen', 'postcode' => '1050', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.6761, 'longitude' => 12.5683],
                ['city' => 'Copenhagen', 'postcode' => '2100', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.6761, 'longitude' => 12.5683],
                ['city' => 'Frederiksberg', 'postcode' => '2000', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.6794, 'longitude' => 12.5346],
                ['city' => 'Helsingør', 'postcode' => '3000', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 56.0361, 'longitude' => 12.6136],
                ['city' => 'Hillerød', 'postcode' => '3400', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.9275, 'longitude' => 12.3014],
                ['city' => 'Hørsholm', 'postcode' => '2970', 'region' => 'Hovedstaden', 'country_code' => 'DK', 'latitude' => 55.8808, 'longitude' => 12.5011],
                ['city' => 'Roskilde', 'postcode' => '4000', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.6415, 'longitude' => 12.0803],
                
                // Sjælland (Region Zealand)
                ['city' => 'Næstved', 'postcode' => '4700', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.2297, 'longitude' => 11.7606],
                ['city' => 'Slagelse', 'postcode' => '4200', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.4053, 'longitude' => 11.3536],
                ['city' => 'Køge', 'postcode' => '4600', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.4580, 'longitude' => 12.1821],
                ['city' => 'Holbæk', 'postcode' => '4300', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.7175, 'longitude' => 11.7169],
                ['city' => 'Ringsted', 'postcode' => '4100', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.4428, 'longitude' => 11.7903],
                ['city' => 'Kalundborg', 'postcode' => '4400', 'region' => 'Sjælland', 'country_code' => 'DK', 'latitude' => 55.6794, 'longitude' => 11.0964],
                
                // Syddanmark (Region of Southern Denmark)
                ['city' => 'Odense', 'postcode' => '5000', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.4038, 'longitude' => 10.4024],
                ['city' => 'Esbjerg', 'postcode' => '6700', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.4869, 'longitude' => 8.4514],
                ['city' => 'Kolding', 'postcode' => '6000', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.4904, 'longitude' => 9.4721],
                ['city' => 'Vejle', 'postcode' => '7100', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.7093, 'longitude' => 9.5357],
                ['city' => 'Horsens', 'postcode' => '8700', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.8607, 'longitude' => 9.8500],
                ['city' => 'Svendborg', 'postcode' => '5700', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.0597, 'longitude' => 10.6067],
                ['city' => 'Sønderborg', 'postcode' => '6400', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 54.9094, 'longitude' => 9.7894],
                ['city' => 'Aabenraa', 'postcode' => '6200', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.0444, 'longitude' => 9.4172],
                ['city' => 'Middelfart', 'postcode' => '5500', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.5050, 'longitude' => 9.7300],
                ['city' => 'Nyborg', 'postcode' => '5800', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.3125, 'longitude' => 10.7897],
                ['city' => 'Assens', 'postcode' => '5610', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.2703, 'longitude' => 9.8992],
                ['city' => 'Faaborg', 'postcode' => '5600', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.0950, 'longitude' => 10.2425],
                ['city' => 'Fredericia', 'postcode' => '7000', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.5656, 'longitude' => 9.7528],
                ['city' => 'Haderslev', 'postcode' => '6100', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.2494, 'longitude' => 9.4878],
                ['city' => 'Kerteminde', 'postcode' => '5300', 'region' => 'Syddanmark', 'country_code' => 'DK', 'latitude' => 55.4492, 'longitude' => 10.6581],
                ['city' => 'Mariager', 'postcode' => '9550', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.6500, 'longitude' => 9.9833],
                
                // Midtjylland (Central Denmark Region)
                ['city' => 'Aarhus', 'postcode' => '8000', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.1629, 'longitude' => 10.2039],
                ['city' => 'Aarhus', 'postcode' => '8200', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.1629, 'longitude' => 10.2039],
                ['city' => 'Randers', 'postcode' => '8900', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.4600, 'longitude' => 10.0364],
                ['city' => 'Silkeborg', 'postcode' => '8600', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.1697, 'longitude' => 9.5450],
                ['city' => 'Herning', 'postcode' => '7400', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.1377, 'longitude' => 8.9762],
                ['city' => 'Viborg', 'postcode' => '8800', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.4507, 'longitude' => 9.4028],
                ['city' => 'Skanderborg', 'postcode' => '8660', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.0347, 'longitude' => 9.9314],
                ['city' => 'Ikast', 'postcode' => '7430', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.1389, 'longitude' => 9.1578],
                ['city' => 'Holstebro', 'postcode' => '7500', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.3600, 'longitude' => 8.6167],
                ['city' => 'Skive', 'postcode' => '7800', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.5667, 'longitude' => 9.0333],
                ['city' => 'Lemvig', 'postcode' => '7620', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.5500, 'longitude' => 8.3167],
                ['city' => 'Ringkøbing', 'postcode' => '6950', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.0833, 'longitude' => 8.2500],
                ['city' => 'Hedensted', 'postcode' => '8722', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 55.7700, 'longitude' => 9.7000],
                ['city' => 'Grenaa', 'postcode' => '8500', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.4167, 'longitude' => 10.8833],
                ['city' => 'Horsens', 'postcode' => '8700', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 55.8607, 'longitude' => 9.8500],
                ['city' => 'Odder', 'postcode' => '8300', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 55.9733, 'longitude' => 10.1500],
                ['city' => 'Samsø', 'postcode' => '8305', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 55.8333, 'longitude' => 10.5833],
                ['city' => 'Rønde', 'postcode' => '8410', 'region' => 'Midtjylland', 'country_code' => 'DK', 'latitude' => 56.3000, 'longitude' => 10.4833],
                
                // Nordjylland (North Denmark Region)
                ['city' => 'Aalborg', 'postcode' => '9000', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.0488, 'longitude' => 9.9217],
                ['city' => 'Aalborg', 'postcode' => '9100', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.0488, 'longitude' => 9.9217],
                ['city' => 'Hjørring', 'postcode' => '9800', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.4583, 'longitude' => 9.9889],
                ['city' => 'Frederikshavn', 'postcode' => '9900', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.4417, 'longitude' => 10.5361],
                ['city' => 'Thisted', 'postcode' => '7700', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.9500, 'longitude' => 8.6833],
                ['city' => 'Brønderslev', 'postcode' => '9700', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.2667, 'longitude' => 9.9500],
                ['city' => 'Hobro', 'postcode' => '9500', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.6333, 'longitude' => 9.8000],
                ['city' => 'Nørresundby', 'postcode' => '9400', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.0667, 'longitude' => 9.9167],
                ['city' => 'Sæby', 'postcode' => '9300', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.3333, 'longitude' => 10.5167],
                ['city' => 'Skagen', 'postcode' => '9990', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.7333, 'longitude' => 10.5833],
                ['city' => 'Hirtshals', 'postcode' => '9850', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 57.5833, 'longitude' => 9.9667],
                ['city' => 'Løgstør', 'postcode' => '9670', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.9667, 'longitude' => 9.2500],
                ['city' => 'Støvring', 'postcode' => '9530', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.8833, 'longitude' => 9.8333],
                ['city' => 'Aars', 'postcode' => '9600', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.8000, 'longitude' => 9.5167],
                ['city' => 'Farsø', 'postcode' => '9640', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.7667, 'longitude' => 9.3333],
                ['city' => 'Nykøbing Mors', 'postcode' => '7900', 'region' => 'Nordjylland', 'country_code' => 'DK', 'latitude' => 56.8000, 'longitude' => 8.8667],
            ];

            foreach ($locations as $location) {
                Location::firstOrCreate(
                    [
                        'city' => $location['city'],
                        'postcode' => $location['postcode'],
                    ],
                    $location
                );
            }
        });
    }
}
