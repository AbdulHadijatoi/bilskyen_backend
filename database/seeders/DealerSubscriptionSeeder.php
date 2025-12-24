<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\Plan;
use App\Models\DealerSubscription;
use App\Models\SubscriptionStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DealerSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $dealers = Dealer::all();
            $plans = Plan::where('is_active', true)->get();
            
            if ($dealers->isEmpty() || $plans->isEmpty()) {
                return;
            }
            
            foreach ($dealers as $dealer) {
                $plan = $plans->random();
                $statusId = $faker->randomElement([
                    SubscriptionStatus::TRIAL,
                    SubscriptionStatus::ACTIVE,
                    SubscriptionStatus::ACTIVE,
                    SubscriptionStatus::ACTIVE,
                ]);
                
                $startsAt = $faker->dateTimeBetween('-1 year', 'now');
                $endsAt = $statusId === SubscriptionStatus::TRIAL 
                    ? (clone $startsAt)->modify('+14 days')
                    : ($faker->boolean(70) ? (clone $startsAt)->modify('+1 year') : null);
                
                DealerSubscription::create([
                    'dealer_id' => $dealer->id,
                    'plan_id' => $plan->id,
                    'subscription_status_id' => $statusId,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'auto_renew' => $faker->boolean(60),
                    'created_at' => $startsAt,
                ]);
            }
        });
    }
}

