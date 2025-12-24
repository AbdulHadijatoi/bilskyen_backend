<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create('da_DK');
            
            // Create a mix of users (buyers, dealers, admins)
            // Some will be linked to dealers later via DealerUserSeeder
            
            // Create admin user
            User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'phone' => $faker->phoneNumber(),
                    'password' => Hash::make('password'),
                    'status_id' => UserStatus::ACTIVE,
                    'email_verified_at' => now(),
                ]
            );
            
            // Create dealer users (will be linked to dealers)
            for ($i = 0; $i < 10; $i++) {
                User::create([
                    'name' => $faker->name(),
                    'email' => $faker->unique()->safeEmail(),
                    'phone' => $faker->phoneNumber(),
                    'password' => Hash::make('password'),
                    'status_id' => $faker->randomElement([
                        UserStatus::ACTIVE,
                        UserStatus::ACTIVE,
                        UserStatus::ACTIVE,
                        UserStatus::INACTIVE,
                    ]), // Mostly active
                    'email_verified_at' => $faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                ]);
            }
            
            // Create buyer users
            for ($i = 0; $i < 15; $i++) {
                User::create([
                    'name' => $faker->name(),
                    'email' => $faker->unique()->safeEmail(),
                    'phone' => $faker->phoneNumber(),
                    'password' => Hash::make('password'),
                    'status_id' => $faker->randomElement([
                        UserStatus::ACTIVE,
                        UserStatus::ACTIVE,
                        UserStatus::INACTIVE,
                    ]),
                    'email_verified_at' => $faker->optional(0.7)->dateTimeBetween('-6 months', 'now'),
                ]);
            }
        });
    }
}

