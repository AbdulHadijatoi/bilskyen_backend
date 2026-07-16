<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * QA follow-up: align Premium plan copy with max_listings, activate Professional PAYG,
 * and normalize homepage/about CMS content that still showed outdated marketing/role text.
 */
return new class extends Migration
{
    public function up(): void
    {
        $premiumDescriptions = [
            'Ideel til aktive forhandlere med op til ca. 60 biler og behov for bedre lead håndtering.',
            'Ideal for active dealers with up to approx. 60 cars and a need for better lead handling.',
            'Ideel til aktive forhandlere med op til ca. 60 biler',
        ];

        DB::table('plans')
            ->where('slug', 'premium')
            ->where(function ($query) use ($premiumDescriptions) {
                foreach ($premiumDescriptions as $description) {
                    $query->orWhere('description', 'like', '%'.$description.'%');
                }
                $query->orWhere('description', 'like', '%ca. 60 biler%')
                    ->orWhere('description', 'like', '%approx. 60%');
            })
            ->update([
                'description' => 'Ideel til aktive forhandlere med op til ca. 200 biler og behov for bedre lead-håndtering.',
                'updated_at' => now(),
            ]);

        // If Premium still has English seed copy mentioning no count, leave it;
        // only force Danish production copy when the 60-car mismatch is present.
        $premium = DB::table('plans')->where('slug', 'premium')->first();
        if ($premium && is_string($premium->description) && str_contains($premium->description, '60')) {
            DB::table('plans')->where('id', $premium->id)->update([
                'description' => 'Ideel til aktive forhandlere med op til ca. 200 biler og behov for bedre lead-håndtering.',
                'updated_at' => now(),
            ]);
        }

        DB::table('plans')
            ->where('slug', 'professional-payg')
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

        $homeUpdates = [
            // Keep inventory-facing number conservative; avoid inflated user/satisfaction claims.
            'stat_1_value' => '10+',
            'stat_1_title' => 'Brugte biler',
            'stat_1_description' => 'Nye annoncer hver dag',
            'stat_2_value' => '30+',
            'stat_2_title' => 'Brugere på platformen',
            'stat_2_description' => 'Købere og sælgere samlet ét sted',
            'stat_3_value' => '24/7',
            'stat_3_title' => 'Digital platform',
            'stat_3_description' => 'Find og sælg bil når det passer dig',
            'stat_4_value' => 'DK',
            'stat_4_title' => 'Fokus på Danmark',
            'stat_4_description' => 'Bygget til det danske bilmarked',
        ];

        foreach ($homeUpdates as $sectionKey => $content) {
            DB::table('page_contents')->updateOrInsert(
                ['page_name' => 'home', 'section_key' => $sectionKey],
                ['content' => $content, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        DB::table('page_contents')
            ->where('page_name', 'about')
            ->where('section_key', 'about_team_member_1_role')
            ->where(function ($query) {
                $query->where('content', 'Ceo & Founder')
                    ->orWhere('content', 'CEO & Founder')
                    ->orWhere('content', 'Ceo & founder');
            })
            ->update([
                'content' => 'CEO & Founder',
                'updated_at' => now(),
            ]);

        // Clear page content caches so public site picks up CMS changes.
        foreach (['home', 'about'] as $pageName) {
            Cache::forget(\App\Models\PageContent::getCacheKey($pageName));
        }
    }

    public function down(): void
    {
        DB::table('plans')
            ->where('slug', 'professional-payg')
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }
};
