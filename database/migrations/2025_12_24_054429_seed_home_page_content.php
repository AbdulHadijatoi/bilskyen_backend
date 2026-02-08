<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\PageContent;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define all home page sections with their default content
        $sections = [
            [
                'page_name' => 'home',
                'section_key' => 'search_title',
                'content' => 'Find Your Perfect Vehicle at Bilskyen',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'search_description',
                'content' => 'Search our inventory to find the perfect match for your needs.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'hero_description',
                'content' => 'Revolutionizing the car buying experience with transparent pricing, quality vehicles, and exceptional customer service.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'featured_vehicles_title',
                'content' => 'Featured Vehicles',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'featured_vehicles_description',
                'content' => 'Explore our selection of quality vehicles ready for you to drive home today.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stats_title',
                'content' => 'Why Choose Bilskyen',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stats_description',
                'content' => "We're committed to providing exceptional service and quality vehicles to our customers.",
            ],
            [
                'page_name' => 'home',
                'section_key' => 'features_title',
                'content' => 'Our Services',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'features_description',
                'content' => 'We provide comprehensive services to make your vehicle purchase smooth and enjoyable.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonials_title',
                'content' => 'Customer Testimonials',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonials_description',
                'content' => 'Hear what our customers have to say about their experience with us.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'footer_cta_title',
                'content' => 'Ready to Find Your Next Vehicle?',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'footer_cta_description',
                'content' => 'Visit our showroom or browse our inventory online. Our team is ready to help you find the perfect vehicle that fits your needs and budget.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'footer_about_description',
                'content' => 'Bilskyen - Driving trust and value with quality pre-owned vehicles for every journey.',
            ],
            // Stats Section - Stat 1
            [
                'page_name' => 'home',
                'section_key' => 'stat_1_value',
                'content' => '100+',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_1_title',
                'content' => 'Quality Vehicles',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_1_description',
                'content' => 'Thoroughly inspected vehicles in our inventory',
            ],
            // Stats Section - Stat 2
            [
                'page_name' => 'home',
                'section_key' => 'stat_2_value',
                'content' => '500+',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_2_title',
                'content' => 'Happy Customers',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_2_description',
                'content' => 'Satisfied customers who found their perfect vehicle',
            ],
            // Stats Section - Stat 3
            [
                'page_name' => 'home',
                'section_key' => 'stat_3_value',
                'content' => '15+',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_3_title',
                'content' => 'Years of Experience',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_3_description',
                'content' => 'Years serving our community with integrity',
            ],
            // Stats Section - Stat 4
            [
                'page_name' => 'home',
                'section_key' => 'stat_4_value',
                'content' => '98%',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_4_title',
                'content' => 'Satisfaction Rate',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'stat_4_description',
                'content' => 'Customer satisfaction based on reviews',
            ],
            // Features Section - Feature 1
            [
                'page_name' => 'home',
                'section_key' => 'feature_1_title',
                'content' => 'Financing Options',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'feature_1_description',
                'content' => 'We work with multiple lenders to find the best financing solutions for your budget.',
            ],
            // Features Section - Feature 2
            [
                'page_name' => 'home',
                'section_key' => 'feature_2_title',
                'content' => 'Vehicle Warranty',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'feature_2_description',
                'content' => 'Extended warranty options to protect your investment and give you peace of mind.',
            ],
            // Features Section - Feature 3
            [
                'page_name' => 'home',
                'section_key' => 'feature_3_title',
                'content' => 'Service Department',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'feature_3_description',
                'content' => 'Professional maintenance and repair services to keep your vehicle in top condition.',
            ],
            // Testimonials Section - Testimonial 1
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_1_name',
                'content' => 'John Davis',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_1_location',
                'content' => 'Copenhagen, Denmark',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_1_quote',
                'content' => 'The team at Bilskyen made buying a car so easy. They were transparent about pricing and helped me find the perfect vehicle for my family.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_1_rating',
                'content' => '5',
            ],
            // Testimonials Section - Testimonial 2
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_2_name',
                'content' => 'Priya Sharma',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_2_location',
                'content' => 'Aarhus, Denmark',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_2_quote',
                'content' => 'I was impressed with their knowledge and no-pressure approach. I got a great deal on my new car and would definitely recommend them.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_2_rating',
                'content' => '5',
            ],
            // Testimonials Section - Testimonial 3
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_3_name',
                'content' => 'Ahmed Khan',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_3_location',
                'content' => 'Odense, Denmark',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_3_quote',
                'content' => 'The financing options they provided were better than I expected. The entire process was smooth and I drove away very happy.',
            ],
            [
                'page_name' => 'home',
                'section_key' => 'testimonial_3_rating',
                'content' => '4',
            ],
        ];

        // Insert sections only if they don't already exist
        foreach ($sections as $section) {
            DB::table('page_contents')->updateOrInsert(
                [
                    'page_name' => $section['page_name'],
                    'section_key' => $section['section_key'],
                ],
                [
                    'content' => $section['content'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Clear the cache after seeding
        $cacheKey = PageContent::getCacheKey('home');
        Cache::forget($cacheKey);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove seeded data (commented out to preserve user edits)
        // DB::table('page_contents')
        //     ->where('page_name', 'home')
        //     ->whereIn('section_key', [
        //         'search_title',
        //         'search_description',
        //         'hero_description',
        //         'featured_vehicles_title',
        //         'featured_vehicles_description',
        //         'stats_title',
        //         'stats_description',
        //         'features_title',
        //         'features_description',
        //         'testimonials_title',
        //         'testimonials_description',
        //         'footer_cta_title',
        //         'footer_cta_description',
        //         'footer_about_description',
        //     ])
        //     ->delete();
        
        // Clear cache
        $cacheKey = PageContent::getCacheKey('home');
        Cache::forget($cacheKey);
    }
};
