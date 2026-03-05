<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Seeds login page content (auth layout sidebar testimonial).
     */
    public function up(): void
    {
        $sections = [
            [
                'page_name' => 'login',
                'section_key' => 'testimonial_quote',
                'content' => 'Bilskyen has revolutionized the way we manage our dealership operations, making everything simple and efficient.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'page_name' => 'login',
                'section_key' => 'testimonial_author',
                'content' => 'Rahif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($sections as $section) {
            DB::table('page_contents')->updateOrInsert(
                [
                    'page_name' => $section['page_name'],
                    'section_key' => $section['section_key'],
                ],
                $section
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('page_contents')
            ->where('page_name', 'login')
            ->delete();
    }
};
