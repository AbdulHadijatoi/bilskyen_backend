<?php

namespace Database\Seeders;

use App\Models\LeadStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $stages = [
                ['id' => LeadStage::NEW, 'name' => 'New'],
                ['id' => LeadStage::CONTACTED, 'name' => 'Contacted'],
                ['id' => LeadStage::QUALIFIED, 'name' => 'Qualified'],
                ['id' => LeadStage::QUOTED, 'name' => 'Quoted'],
                ['id' => LeadStage::NEGOTIATING, 'name' => 'Negotiating'],
                ['id' => LeadStage::WON, 'name' => 'Won'],
                ['id' => LeadStage::LOST, 'name' => 'Lost'],
            ];

            foreach ($stages as $stage) {
                LeadStage::updateOrCreate(
                    ['id' => $stage['id']],
                    ['name' => $stage['name']]
                );
            }
        });
    }
}

