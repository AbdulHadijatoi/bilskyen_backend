<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $topics = [
                'Top 10 Electric Vehicles in Denmark',
                'How to Choose the Right Car for Your Family',
                'Maintenance Tips for Your Vehicle',
                'Understanding Vehicle Financing Options',
                'The Future of Electric Vehicles',
                'Best SUVs for Danish Roads',
                'Winter Driving Tips',
                'Buying vs Leasing: What\'s Right for You?',
            ];
            
            foreach ($topics as $topic) {
                $slug = Str::slug($topic);
                $publishedAt = $faker->dateTimeBetween('-6 months', 'now');
                
                Blog::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $topic,
                        'content' => $faker->paragraphs(15, true),
                        'meta_title' => $topic . ' - Denmark Marketplace',
                        'meta_description' => $faker->sentence(15),
                        'published_at' => $publishedAt,
                    ]
                );
            }
        });
    }
}

