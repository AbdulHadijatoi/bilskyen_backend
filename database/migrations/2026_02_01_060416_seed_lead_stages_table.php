<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Constants\LeadStage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $stages = [
            [
                'id' => LeadStage::NEW,
                'name' => 'New',
            ],
            [
                'id' => LeadStage::CONTACTED,
                'name' => 'Contacted',
            ],
            [
                'id' => LeadStage::QUALIFIED,
                'name' => 'Qualified',
            ],
            [
                'id' => LeadStage::QUOTED,
                'name' => 'Quoted',
            ],
            [
                'id' => LeadStage::NEGOTIATING,
                'name' => 'Negotiating',
            ],
            [
                'id' => LeadStage::WON,
                'name' => 'Won',
            ],
            [
                'id' => LeadStage::LOST,
                'name' => 'Lost',
            ],
        ];

        // Truncate the table first
        Schema::disableForeignKeyConstraints();
        DB::table('lead_stages')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($stages as $stage) {
            DB::table('lead_stages')->insert([
                'id' => $stage['id'],
                'name' => $stage['name'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('lead_stages')->truncate();
        Schema::enableForeignKeyConstraints();
    }
};
