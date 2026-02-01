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
                'id' => LeadCategory::PRICE_NEGOTIATION_REQUEST,
                'name' => 'Price Negotiation Request',
                'description' => 'Lead generated from price negotiation request',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeadCategory::FINANCING_REQUEST,
                'name' => 'Financing Request',
                'description' => 'Lead generated from financing request',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeadCategory::WHATSAPP_CLICKED,
                'name' => 'WhatsApp Clicked',
                'description' => 'Lead generated when user clicks WhatsApp contact button',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeadCategory::EMAIL_CLICKED,
                'name' => 'Email Clicked',
                'description' => 'Lead generated when user clicks dealer email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => LeadCategory::ENQUIRY_FORM_SUBMISSION,
                'name' => 'Enquiry Form Submission',
                'description' => 'Lead generated from enquiry form submission',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // truncate the table first
        Schema::disableForeignKeyConstraints();
        DB::table('leads')->truncate();
        DB::table('lead_categories')->truncate();
        Schema::enableForeignKeyConstraints();

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
            'Price Negotiation Request',
            'Financing Request',
            'WhatsApp Clicked',
            'Email Clicked',
            'Enquiry Form Submission',
        ])->delete();
    }
};
