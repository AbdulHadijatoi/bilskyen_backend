<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Constants\LeadCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('lead_categories')->insertOrIgnore([
            'id' => LeadCategory::EXCHANGE_REQUEST,
            'name' => 'Exchange Request',
            'description' => 'Lead generated when user submits an exchange/trade-in request',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lead_categories')->where('name', 'Exchange Request')->delete();
    }
};
