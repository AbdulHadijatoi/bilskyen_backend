<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPriceHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PlanPriceHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $plans = Plan::all();
            
            if ($plans->isEmpty()) {
                return;
            }
            
            // Define base prices for each plan (in DKK cents)
            $basePrices = [
                'basic' => ['monthly' => 29900, 'yearly' => 299000], // 299 DKK/month, 2990 DKK/year
                'professional' => ['monthly' => 79900, 'yearly' => 799000], // 799 DKK/month, 7990 DKK/year
                'enterprise' => ['monthly' => 199900, 'yearly' => 1999000], // 1999 DKK/month, 19990 DKK/year
                'trial' => ['monthly' => 0, 'yearly' => 0], // Free
            ];
            
            foreach ($plans as $plan) {
                $prices = $basePrices[$plan->slug] ?? ['monthly' => 0, 'yearly' => 0];
                
                // Create current price history
                PlanPriceHistory::create([
                    'plan_id' => $plan->id,
                    'price' => $prices['monthly'],
                    'currency' => 'DKK',
                    'billing_cycle' => 'monthly',
                    'starts_at' => now()->subMonths(6),
                    'ends_at' => null,
                ]);
                
                PlanPriceHistory::create([
                    'plan_id' => $plan->id,
                    'price' => $prices['yearly'],
                    'currency' => 'DKK',
                    'billing_cycle' => 'yearly',
                    'starts_at' => now()->subMonths(6),
                    'ends_at' => null,
                ]);
            }
        });
    }
}

