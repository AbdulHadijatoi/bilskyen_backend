<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadStageHistory;
use App\Models\User;
use App\Models\LeadStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class LeadStageHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $leads = Lead::all();
            $dealerStaff = User::whereHas('dealers')->get();
            $leadStages = LeadStage::all();
            
            if ($leads->isEmpty() || $dealerStaff->isEmpty()) {
                return;
            }
            
            // Create stage history for leads that have progressed
            foreach ($leads as $lead) {
                $currentStage = $lead->leadStage;
                $stageOrder = $leadStages->pluck('id')->toArray();
                $currentIndex = array_search($currentStage->id, $stageOrder);
                
                // If lead has progressed beyond NEW stage, create history
                if ($currentIndex > 0) {
                    $changedBy = $dealerStaff->random();
                    
                    // Create history entries for each stage transition
                    for ($i = 1; $i <= $currentIndex; $i++) {
                        $fromStageId = $i === 1 ? LeadStage::NEW : $stageOrder[$i - 1];
                        $toStageId = $stageOrder[$i];
                        
                    // Ensure dates are in correct order
                    $startDate = $lead->created_at < $lead->last_activity_at 
                        ? $lead->created_at 
                        : $lead->last_activity_at;
                    $endDate = $lead->created_at < $lead->last_activity_at 
                        ? $lead->last_activity_at 
                        : $lead->created_at;
                    
                    LeadStageHistory::create([
                        'lead_id' => $lead->id,
                        'from_stage_id' => $fromStageId,
                        'to_stage_id' => $toStageId,
                        'changed_by_user_id' => $changedBy->id,
                        'changed_at' => $faker->dateTimeBetween($startDate, $endDate ?: 'now'),
                    ]);
                    }
                }
            }
        });
    }
}

