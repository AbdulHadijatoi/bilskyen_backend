<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Dealer;
use App\Models\LeadStage;
use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $vehicles = Vehicle::where('vehicle_list_status_id', \App\Models\VehicleListStatus::PUBLISHED)->get();
            $buyers = User::whereHas('roles', function ($query) {
                $query->where('name', 'seller');
            })->get();
            $dealers = Dealer::all();
            $dealerStaff = User::whereHas('dealers')->get();
            $leadStages = LeadStage::all();
            $sources = Source::all();
            
            if ($vehicles->isEmpty() || $buyers->isEmpty() || $dealers->isEmpty()) {
                return;
            }
            
            // Create leads for published vehicles
            foreach ($vehicles->take(20) as $vehicle) {
                $buyer = $buyers->random();
                $dealer = $vehicle->dealer;
                $assignedUser = $dealerStaff->whereIn('id', $dealer->users->pluck('id'))->random();
                $leadStage = $faker->randomElement($leadStages);
                $source = $sources->random();
                
                Lead::create([
                    'vehicle_id' => $vehicle->id,
                    'buyer_user_id' => $buyer->id,
                    'dealer_id' => $dealer->id,
                    'assigned_user_id' => $faker->boolean(70) ? $assignedUser->id : null,
                    'lead_stage_id' => $leadStage->id,
                    'source_id' => $source->id,
                    'last_activity_at' => $faker->dateTimeBetween('-30 days', 'now'),
                    'created_at' => $faker->dateTimeBetween('-60 days', 'now'),
                ]);
            }
        });
    }
}

