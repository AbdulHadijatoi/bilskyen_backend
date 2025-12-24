<?php

namespace App\Constants;

class Vehicles
{
    public const MAKES = [
        'Acura',
        'Alfa Romeo',
        'Aston Martin',
        'Audi',
        'Bentley',
        'BMW',
        'Bugatti',
        'Buick',
        'Cadillac',
        'Chevrolet',
        'Chrysler',
        'Citroën',
        'Dacia',
        'Daewoo',
        'Daihatsu',
        'Dodge',
        'Donkervoort',
        'DS',
        'Ferrari',
        'Fiat',
        'Fisker',
        'Ford',
        'Genesis',
        'GMC',
        'Honda',
        'Hummer',
        'Hyundai',
        'Infiniti',
        'Isuzu',
        'Jaguar',
        'Jeep',
        'Kia',
        'Koenigsegg',
        'KTM',
        'Lada',
        'Lamborghini',
        'Lancia',
        'Land Rover',
        'Lexus',
        'Lincoln',
        'Lotus',
        'Maserati',
        'Maybach',
        'Mazda',
        'McLaren',
        'Mercedes-Benz',
        'Mercury',
        'Mini',
        'Mitsubishi',
        'Nissan',
        'Opel',
        'Pagani',
        'Peugeot',
        'Polestar',
        'Pontiac',
        'Porsche',
        'RAM',
        'Renault',
        'Rolls-Royce',
        'Saab',
        'Saturn',
        'Scion',
        'Seat',
        'Škoda',
        'Smart',
        'SsangYong',
        'Subaru',
        'Suzuki',
        'Tesla',
        'Toyota',
        'Volkswagen',
        'Volvo',
        'Maruti Suzuki',
        'Tata Motors',
        'Mahindra & Mahindra',
        'Ashok Leyland',
        'Force Motors',
        'MG Motor',
        'Skoda',
        'BYD',
        'VinFast',
    ];

    public const TYPES = [
        'Sedan',
        'SUV',
        'Truck',
        'Hatchback',
        'MUV',
        'Coupe',
        'Convertible',
        'Pickup Truck',
        'Crossover',
        'Compact SUV',
        'Compact Sedan',
        'Micro Car',
        'Station Wagon',
        'MPV',
        'Crossover SUV',
        'Coupe SUV',
        '4-Door Coupe',
        'Sportback',
        'Electric Car',
        'Hybrid Car',
        'Mini SUV',
        'Van',
        'Minivan',
        'Spyder',
        'Cabriolet',
        'Notchback',
        'Fastback',
        'Saloon',
        'Off-Roader',
        'Luxury Sedan',
        'Luxury SUV',
        'Performance Car',
        'Sports Car',
        'CNG Car',
        'Diesel Car',
        'Petrol Car',
    ];

    public const STATUSES = [
        'Available',
        'Sold',
        'Pending Sale',
        'Pending Purchase',
        'Reserved',
        'In Service',
        'Under Maintenance',
        'Not Available',
    ];

    public const TRANSMISSION_TYPES = [
        'Manual Transmission (MT)',
        'Automated Manual Transmission (AMT)',
        'Intelligent Manual Transmission (iMT)',
        'Continuously Variable Transmission (CVT)',
        'Dual-Clutch Transmission (DCT)',
        'Torque Converter Automatic Transmission',
        'Tiptronic Transmission',
    ];

    public const FUEL_TYPES = [
        'Petrol',
        'Diesel',
        'Compressed Natural Gas (CNG)',
        'Liquefied Petroleum Gas (LPG)',
        'Electric Vehicle (EV)',
        'Hybrid Electric Vehicle (HEV)',
        'Plug-in Hybrid Electric Vehicle (PHEV)',
    ];

    public const CONDITIONS = [
        'Excellent',
        'Good',
        'Fair',
        'Needs Work',
    ];

    public const BLACKLIST_TYPES = [
        'Unpaid traffic fines',
        'Road tax not paid',
        'No insurance',
        'Pollution rule break',
        'Loan not paid',
        'Fake or wrong papers',
        'Bad accident (total loss)',
        'Illegal changes to vehicle',
        'Import duty not paid',
    ];

    /**
     * Get vehicle years array from 1885 to current year
     */
    public static function getYears(): array
    {
        $currentYear = (int) date('Y');
        $years = [];
        for ($year = $currentYear; $year >= 1885; $year--) {
            $years[] = $year;
        }
        return $years;
    }
}


