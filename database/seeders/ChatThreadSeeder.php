<?php

namespace Database\Seeders;

use App\Models\ChatThread;
use App\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ChatThreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $leads = Lead::all();
            
            if ($leads->isEmpty()) {
                return;
            }
            
            // Create chat threads for some leads
            foreach ($leads->take(15) as $lead) {
                ChatThread::create([
                    'lead_id' => $lead->id,
                    'created_at' => $faker->dateTimeBetween($lead->created_at, 'now'),
                ]);
            }
        });
    }
}

