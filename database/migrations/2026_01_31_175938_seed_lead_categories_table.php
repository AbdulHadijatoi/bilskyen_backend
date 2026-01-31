<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'name' => 'Enquire',
                'description' => 'Lead generated from Enquire button click',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'View Phone Number',
                'description' => 'Lead generated when user views phone number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Contact Form',
                'description' => 'Lead generated from contact form submission',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Test Drive Request',
                'description' => 'Lead generated from test drive request',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Price Inquiry',
                'description' => 'Lead generated from price inquiry',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('lead_categories')->insertOrIgnore([
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
            'Enquire',
            'View Phone Number',
            'Contact Form',
            'Test Drive Request',
            'Price Inquiry',
        ])->delete();
    }
};
