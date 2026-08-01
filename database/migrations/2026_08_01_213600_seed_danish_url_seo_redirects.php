<?php

use App\Models\SeoRedirect;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seed permanent 301s from English public paths to Danish equivalents.
     *
     * @var list<array{from_path: string, to_path: string, match_type: string}>
     */
    private array $redirects = [
        ['from_path' => '/vehicles', 'to_path' => '/biler', 'match_type' => 'prefix'],
        ['from_path' => '/about', 'to_path' => '/om-os', 'match_type' => 'exact'],
        ['from_path' => '/contact', 'to_path' => '/kontakt', 'match_type' => 'exact'],
        ['from_path' => '/privacy-policy', 'to_path' => '/privatlivspolitik', 'match_type' => 'exact'],
        ['from_path' => '/terms-of-service', 'to_path' => '/vilkaar', 'match_type' => 'exact'],
        ['from_path' => '/account-deletion', 'to_path' => '/slet-konto', 'match_type' => 'exact'],
        ['from_path' => '/for-dealers', 'to_path' => '/for-forhandlere', 'match_type' => 'prefix'],
        ['from_path' => '/for-staff', 'to_path' => '/for-medarbejdere', 'match_type' => 'prefix'],
        ['from_path' => '/sell-your-car', 'to_path' => '/saelg-din-bil', 'match_type' => 'prefix'],
        ['from_path' => '/lp', 'to_path' => '/guides', 'match_type' => 'prefix'],
        ['from_path' => '/favorites', 'to_path' => '/favoritter', 'match_type' => 'prefix'],
        ['from_path' => '/profile', 'to_path' => '/profil', 'match_type' => 'exact'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('seo_redirects')) {
            return;
        }

        foreach ($this->redirects as $row) {
            SeoRedirect::query()->updateOrCreate(
                ['from_path' => $row['from_path']],
                [
                    'to_path' => $row['to_path'],
                    'match_type' => $row['match_type'],
                    'redirect_type' => 301,
                    'is_active' => true,
                ]
            );
        }

        Cache::forget('seo_redirects_map');
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_redirects')) {
            return;
        }

        $fromPaths = array_column($this->redirects, 'from_path');
        SeoRedirect::query()->whereIn('from_path', $fromPaths)->delete();
        Cache::forget('seo_redirects_map');
    }
};
