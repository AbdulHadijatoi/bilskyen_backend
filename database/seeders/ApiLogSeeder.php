<?php

namespace Database\Seeders;

use App\Models\ApiLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ApiLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $services = ['VehicleAPI', 'LeadAPI', 'UserAPI', 'PaymentAPI', 'NotificationAPI'];
            $endpoints = [
                '/api/vehicles',
                '/api/vehicles/{id}',
                '/api/leads',
                '/api/users',
                '/api/subscriptions',
                '/api/analytics',
            ];
            
            $statusCodes = [200, 200, 200, 201, 400, 401, 404, 500]; // Mostly success, some errors
            
            // Create API logs
            for ($i = 0; $i < 30; $i++) {
                ApiLog::create([
                    'api_service' => $faker->randomElement($services),
                    'endpoint' => $faker->randomElement($endpoints),
                    'status_code' => $faker->randomElement($statusCodes),
                    'execution_time_ms' => $faker->numberBetween(50, 2000),
                    'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
                ]);
            }
        });
    }
}

