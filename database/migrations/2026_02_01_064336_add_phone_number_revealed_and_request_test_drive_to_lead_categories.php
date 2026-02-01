<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Constants\LeadCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'id' => LeadCategory::PHONE_NUMBER_REVEALED,
                'name' => 'Phone Number Revealed',
                'description' => 'Lead generated when user clicks to reveal dealer phone number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeadCategory::REQUEST_TEST_DRIVE,
                'name' => 'Request Test Drive',
                'description' => 'Lead generated when user requests a test drive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('lead_categories')->insertOrIgnore([
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => $category['created_at'],
                'updated_at' => $category['updated_at'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('lead_categories')->whereIn('name', [
            'Phone Number Revealed',
            'Request Test Drive',
        ])->delete();
    }
};
