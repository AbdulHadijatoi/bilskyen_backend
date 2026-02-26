<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandAndModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'Abarth', 'AC', 'Aiways', 'Alfa Romeo', 'Alpina', 'Alpine', 'Aston Martin', 'Auburn', 'Audi', 'Austin',
            'Austin Healey', 'Bentley', 'BMW', 'Buick', 'BYD', 'Cadillac', 'Chevrolet', 'Chrysler', 'Citroën', 'Corvette',
            'Cupra', 'Dacia', 'Daewoo', 'Daimler', 'Dallara', 'DeTomaso', 'DFSK', 'DKW', 'Dodge', 'DS',
            'Excalibur', 'Ferrari', 'Fiat', 'firefly', 'Fisker', 'Ford', 'Honda', 'Hongqi', 'Hummer', 'Hyundai',
            'JAC', 'Jaguar', 'Jeep', 'Jensen', 'Kalmar', 'KGM', 'Kia', 'KTM', 'Lada', 'Lamborghini',
            'Lancia', 'Land Rover', 'Leapmotor', 'Lexus', 'Lincoln', 'Lindebjerg', 'Lloyd', 'Lotus', 'Lucid', 'Lynk & Co',
            'MAN', 'Maserati', 'Matra', 'Maxus', 'Maybach', 'Mazda', 'McLaren', 'Mercedes', 'Messerschmitt', 'MG',
            'Micro', 'MINI', 'Mitsubishi', 'Morgan', 'Morris', 'Navor', 'NIO', 'Nissan', 'NSU', 'Oldsmobile',
            'Omoda', 'Opel', 'OScar', 'Overland', 'Peugeot', 'Plymouth', 'Polestar', 'Pontiac', 'Porsche', 'Reliant',
            'Renault', 'Rolls-Royce', 'Rover', 'Seat', 'Seres', 'Singer', 'Skoda', 'Skyworth', 'Smart', 'Ssangyong',
            'Subaru', 'Superformance', 'Suzuki', 'Saab', 'Tesla', 'Toyota', 'Trabant', 'Triumph', 'Volvo', 'Voyah',
            'VW', 'Xpeng', 'Yugo', 'Zeekr',
        ];

        $modelsByBrand = [
            'VW' => [
                '1100', '113', '1200', '1300', '1302', '1302L', '1303', '1500', '1600', 'Amarok', 'Arteon',
                'Beach Buggy', 'Beetle-Serie', 'The Beetle', 'New Beetle', 'Bora', 'Caddy-Serie', 'Caddy', 'Caddy Maxi',
                'California', 'Caravelle-Serie', 'Caravelle', 'e-Caravelle', 'Crafter-Serie', 'Crafter 35', 'Eos', 'Fox',
                'Golf-Serie', 'Golf VII', 'Golf VIII', 'e-Golf VII', 'Golf Sportsvan', 'Golf VI', 'Golf Plus', 'Golf V',
                'Golf IV', 'Golf III', 'Golf I', 'Golf II', 'Golf II Country', 'ID.3', 'ID.4', 'ID.5', 'ID.7', 'ID.Buzz',
                'Jetta', 'Karmann Ghia', 'Lupo', 'Multivan', 'Passat-Serie', 'Passat', 'CC', 'Passat Alltrack', 'Passat CC',
                'Phaeton', 'Polo-Serie', 'Polo', 'Polo Cross', 'Scirocco', 'Sharan', 'T-Cross', 'T-Roc', 'Taigo',
                'Tiguan-Serie', 'Tiguan', 'Tiguan Allspace', 'Touareg', 'Touran-Serie', 'Touran', 'Transporter-Serie',
                'T1', 'T2', 'Transporter', 'T3', 'Up!-Serie', 'Up!', 'e-Up!',
            ],
            'Mercedes' => [
                '190-Serie', '190 E', '190 C', '190', '190 B', '200-Serie', '230 CE', '230 E', '280 CE', '230 C', '230 TE',
                '280 E', '300', '300 E', '500 E', 'A-Klasse', 'A200', 'A250 e', 'A200 d', 'A180 d', 'A180', 'A45', 'A220 d',
                'A220', 'A250', 'A160', 'A35', 'A150', 'A140', 'A170', 'AMG GT-Serie', 'AMG GT S', 'AMG GT 63', 'AMG GT C',
                'AMG GT', 'AMG GT 43', 'AMG GT 53', 'AMG GT R', 'B-Klasse', 'B200', 'B180', 'B200 d', 'B180 d', 'B220 d',
                'B-Electric', 'B220', 'B250', 'B250 e', 'B150', 'C-Klasse', 'C220 d', 'C200', 'C220', 'C63', 'C300 e',
                'C300 de', 'C43', 'C300', 'C180', 'C250', 'C200 d', 'C350 e', 'C400', 'C250 d', 'C300 d', 'C240', 'C230',
                'C350', 'C280', 'C320', 'C36', 'Citan-Klasse', 'eCitan', 'CL-Klasse', 'CL500', 'CLA-Klasse', 'CLA200',
                'CLA250 e', 'CLA250+', 'CLA220 d', 'CLA200 d', 'CLA220', 'CLA250', 'CLA180', 'CLA35', 'CLA350', 'CLA45',
                'CLE-Klasse', 'CLE53', 'CLE300', 'CLK-Klasse', 'CLK320', 'CLK200', 'CLK230', 'CLK280', 'CLK350', 'CLK63',
                'CLK500', 'CLK55', 'CLS-Klasse', 'CLS350', 'CLS500', 'CLS350 d', 'CLS63', 'CLS220', 'CLS320', 'CLS400',
                'CLS400 d', 'CLS450', 'CLS55', 'E-Klasse', 'E300 de', 'E220 d', 'E200', 'E220', 'E350', 'E63', 'E300 e',
                'E350 d', 'E53', 'E400', 'E250', 'E300', 'E43', 'E450', 'E280', 'E500', 'E300 d', 'E400 d', 'E240', 'E350 e',
                'E200 d', 'E320', 'E55', 'E230', 'E270', 'E430', 'EQA-Klasse', 'EQA250+', 'EQA250', 'EQA350', 'EQA300',
                'EQB-Klasse', 'EQB250+', 'EQB300', 'EQB350', 'EQB250', 'EQC-Klasse', 'EQC400', 'EQE-Klasse', 'EQE350',
                'EQE350 SUV', 'EQE350+', 'EQE300', 'EQE350+ SUV', 'EQE43', 'EQE300 SUV', 'EQE500 SUV', 'EQE43 SUV', 'EQE53',
                'EQE53 SUV', 'EQE500', 'EQS-Klasse', 'EQS450+', 'EQS580', 'EQS450 SUV', 'EQS450+ SUV', 'EQS53', 'EQS350',
                'EQS580 SUV', 'EQT200', 'EQV-Klasse', 'EQV300', 'G-Klasse', 'G63', 'G580', 'G500', 'G350', 'G350 d', 'G400',
                'G400 d', 'GE500', 'GL-Klasse', 'GL420', 'GL500', 'GL550', 'GLA-Klasse', 'GLA250 e', 'GLA200', 'GLA250',
                'GLA200 d', 'GLA220 d', 'GLA45', 'GLA220', 'GLA35', 'GLB-Klasse', 'GLB200', 'GLB200 d', 'GLB250', 'GLB220 d',
                'GLC-Klasse', 'GLC43', 'GLC300 de', 'GLC220 d', 'GLC300 e', 'GLC250 d', 'GLC350 d', 'GLC63', 'GLC350 e',
                'GLC300', 'GLC250', 'GLC300 d', 'GLC200', 'GLC400 d', 'GLC400 e', 'GLE-Klasse', 'GLE350 de', 'GLE350 d',
                'GLE450', 'GLE63', 'GLE400 d', 'GLE53', 'GLE300 d', 'GLE350 e', 'GLE43', 'GLE580', 'GLE400', 'GLE450 d',
                'GLE500', 'GLE500 e', 'GLE400 e', 'GLK-Klasse', 'GLK220', 'GLK350', 'GLK200', 'GLS-Klasse', 'GLS400 d',
                'GLS63', 'GLS400', 'GLS350 d', 'GLS450 d', 'GLS600', 'M-Klasse', 'ML350', 'ML63', 'ML320', 'ML500', 'R-Klasse',
                'R320', 'S-Klasse', 'S63', 'S500', 'S580 e', 'S350', 'S350 d', 'S560', '220 SE', '280 S', '300 SE', '380 SEC',
                '500 SEL', 'S320', 'S400 d', '220 S', '250 SE', '280 SE', '350 SE', '400 SE', '420 SE', '450 SEL', '500 SEC',
                '560 SEL', 'S400', 'S580', 'SL-Klasse', 'SL500', 'SL63', '280 SL', '560 SL', 'SL55', 'SL600', '190 SL',
                '230 SL', '350 SL', '450 SL', 'SL320', 'SL350', '300 SL', '500 SL', 'SL400', 'SL65', 'SLK-Klasse', 'SLK200',
                'SLK230', 'SLK350', 'SLK280', 'SLK250', 'SLK320', 'SLK55', 'Sprinter-Klasse', 'Sprinter 317', 'Sprinter 211',
                'Sprinter 213', 'Sprinter 215', 'Sprinter 217', 'Sprinter 315', 'Sprinter 319', 'V-Klasse', 'V250 d', 'V300 d',
                'V220 d', 'V230', 'Viano', 'Vito-Klasse', 'Vito 116', 'eVito 129', 'Vito 114', 'Vito 113',
            ],
            'BMW' => [
                '1-Serie', '118i', '118d', '120d', '116i', 'M135i', '116d', '120i', '135i', '114d', '130i', '1602',
                '2-Serie', '225xe', '218i', '220d', '225e', '218d', '220i', 'M2', 'M240i', 'M235i', '216i', '2000 CS', '2002',
                '3-Serie', '330e', '320d', '320i', '330i', 'M3', '320e', '325i', '318d', '335i', '330d', '316i', '325d',
                '330Ci', '318Ci', '318i', '320', '323i', '328i', '316d', '320Ci', '316Ti', '318Ti', '325iX', '330Xi', '335Xi',
                '335d', 'M340i', '4-Serie', 'i4', '420d', 'M4', '440i', '420i', '428i', '430i', '435i', '430d', 'M440i',
                '418d', '435d', '5-Serie', 'i5', '530e', '520d', '530d', '545e', 'M5', '530i', '535d', '520i', '525d', 'M550i',
                '523i', 'M550d', '525i', '528i', '535i', '502', '518', '524', '530Xi', '540i', '550i', '6-Serie', '640i', '645Ci',
                'M6', '630i', '630CS', '635CSi', '650i', 'M635 CSi', '7-Serie', 'i7', '730d', '750i', '740d', '740i', '745e',
                '728i', '740e', '745Le', '750Li', 'M760Li', '730Ld', '740iL', '8-Serie', 'M850i', '840i', 'M8', '840d',
                'i3-Serie', 'i3', 'i3s', 'i8', 'Isetta', 'X-Serie', 'iX3', 'iX1', 'X5', 'iX', 'X1', 'X3', 'iX2', 'X6', 'X2',
                'X4', 'X7', 'XM', 'Z-Serie', 'Z4', 'Z3',
            ],
            'Audi' => [
                '200', '80-Serie', '80', 'Coupé', 'Cabriolet', 'Coupé GT', 'A1-Serie', 'A1', 'S1', 'A2', 'A3-Serie', 'A3',
                'S3', 'RS3', 'A4-Serie', 'A4', 'S4', 'A4 allroad', 'RS4', 'A5-Serie', 'A5', 'S5', 'RS5', 'A6-Serie', 'A6',
                'A6 e-tron', 'RS6', 'S6', 'A6 allroad', 'S6 e-tron', 'A7-Serie', 'A7', 'RS7', 'S7', 'A8-Serie', 'A8', 'S8',
                'e-tron GT-Serie', 'e-tron GT', 'RS e-tron GT', 'S e-tron GT', 'Q2-Serie', 'Q2', 'SQ2', 'Q3-Serie', 'Q3',
                'RS Q3', 'Q4 e-tron', 'Q5-Serie', 'Q5', 'SQ5', 'Q6-Serie', 'Q6 e-tron', 'SQ6 e-tron', 'Q7-Serie', 'Q7', 'SQ7',
                'Q8-Serie', 'e-tron', 'Q8 e-tron', 'e-tron S', 'Q8', 'RS Q8', 'SQ8', 'SQ8 e-tron', 'R8', 'TT-Serie', 'TT',
                'TTS', 'TT RS',
            ],
            'Skoda' => [
                'Citigo-Serie', 'Citigo', 'Citigo-e', 'Elroq', 'Enyaq', 'Fabia', 'Felicia', 'Kamiq', 'Karoq-Serie', 'Karoq',
                'Kodiaq-Serie', 'Kodiaq', 'Octavia-Serie', 'Octavia', 'Rapid', 'Roomster-Serie', 'Roomster', 'Scala',
                'Superb-Serie', 'Superb', 'Yeti-Serie', 'Yeti', 'Yeti Outdoor',
            ],
            'Ford' => [
                'B-MAX', 'Bronco', 'C-MAX-Serie', 'C-MAX', 'Grand C-MAX', 'Focus C-MAX', 'Capri', 'EcoSport', 'Edge',
                'Escort', 'Explorer', 'F-serie', 'F-150', 'Fiesta', 'Focus', 'Fusion', 'Galaxy', 'Granada', 'GT', 'Ka-Serie',
                'Ka', 'Ka+', 'Kuga', 'Mondeo', 'Mustang', 'Mustang Mach-E', 'Puma', 'S-MAX', 'Sierra', 'Taunus', 'Thunderbird',
                'Tourneo Connect', 'Tourneo Custom-Serie', 'E-Tourneo Custom 340S', 'Tourneo Custom 320L', 'E-Tourneo Custom 340L',
                'Tourneo Custom 320S', 'Tourneo Custom 300L', 'Tourneo Custom 310L', 'Tourneo Custom 340S', 'Town Car',
                'Transit Custom-Serie', 'Transit Custom Kombi 310S', 'Transit Custom Kombi 320S', 'E-Transit Custom Kombi 320S',
                'Transit Custom Kombi 310L', 'Transit Custom Kombi 320L', 'Transit Custom Kombi 340L', 'E-Transit Custom Kombi 340L',
                'E-Transit Custom Kombi 340S', 'Transit-Serie', 'Transit 350 L3 Kombi', 'E-Transit 350 L3 Kombi', 'Transit 310 L2 Kombi',
                'Transit 350 L2 Kombi', 'V8',
            ],
            'Volvo' => [
                '264', '480', '850', '940', '960', 'Amazon', 'C30', 'C40', 'C70', 'EC40', 'ES90', 'EX30', 'EX30 CC',
                'EX40', 'EX90', 'P1800-Serie', 'P1800', 'P1800 E', 'P1800 ES', 'PV-Serie', 'PV444', 'PV544', 'S40',
                'S60-Serie', 'S60', 'S70', 'S80', 'S90', 'V40-Serie', 'V40', 'V40 CC', 'V50', 'V60-Serie', 'V60', 'V60 CC',
                'V70', 'V90-Serie', 'V90', 'V90 CC', 'XC40', 'XC60', 'XC70', 'XC90',
            ],
            'Tesla' => [
                'Model 3', 'Model S', 'Model X', 'Model Y', 'Roadster',
            ],
        ];

        DB::transaction(function () use ($brands, $modelsByBrand) {
            foreach ($brands as $name) {
                Brand::firstOrCreateInsensitive(['name' => $name]);
            }

            foreach ($modelsByBrand as $brandName => $modelNames) {
                $brand = Brand::where('name', $brandName)->first();
                if (!$brand) {
                    continue;
                }
                foreach ($modelNames as $modelName) {
                    VehicleModel::firstOrCreateInsensitive(
                        [
                            'brand_id' => $brand->id,
                            'name' => $modelName,
                        ]
                    );
                }
            }
        });
    }
}
