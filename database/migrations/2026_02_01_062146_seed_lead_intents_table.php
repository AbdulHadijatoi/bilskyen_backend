<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Constants\LeadIntent;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $intents = [
            [
                'id' => LeadIntent::LOW,
                'name' => 'Low Intent',
            ],
            [
                'id' => LeadIntent::MEDIUM,
                'name' => 'Medium Intent',
            ],
            [
                'id' => LeadIntent::HIGH,
                'name' => 'High Intent',
            ],
            [
                'id' => LeadIntent::VERY_HIGH,
                'name' => 'Very High Intent',
            ],
        ];

        // Truncate the table first
        Schema::disableForeignKeyConstraints();
        DB::table('lead_intents')->truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($intents as $intent) {
            DB::table('lead_intents')->insert([
                'id' => $intent['id'],
                'name' => $intent['name'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('lead_intents')->truncate();
        Schema::enableForeignKeyConstraints();
    }
};
