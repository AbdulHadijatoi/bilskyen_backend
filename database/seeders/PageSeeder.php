<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Faker::create();
            
            $pages = [
                [
                    'title' => 'About Us',
                    'slug' => 'about-us',
                    'content' => $faker->paragraphs(10, true),
                    'meta_title' => 'About Us - Denmark Marketplace',
                    'meta_description' => 'Learn more about our vehicle marketplace platform.',
                ],
                [
                    'title' => 'Contact',
                    'slug' => 'contact',
                    'content' => $faker->paragraphs(5, true),
                    'meta_title' => 'Contact Us - Denmark Marketplace',
                    'meta_description' => 'Get in touch with our team.',
                ],
                [
                    'title' => 'Terms of Service',
                    'slug' => 'terms-of-service',
                    'content' => $faker->paragraphs(15, true),
                    'meta_title' => 'Terms of Service - Denmark Marketplace',
                    'meta_description' => 'Read our terms of service.',
                ],
                [
                    'title' => 'Privacy Policy',
                    'slug' => 'privacy-policy',
                    'content' => $faker->paragraphs(12, true),
                    'meta_title' => 'Privacy Policy - Denmark Marketplace',
                    'meta_description' => 'Our privacy policy and data protection information.',
                ],
                [
                    'title' => 'How It Works',
                    'slug' => 'how-it-works',
                    'content' => $faker->paragraphs(8, true),
                    'meta_title' => 'How It Works - Denmark Marketplace',
                    'meta_description' => 'Learn how to buy and sell vehicles on our platform.',
                ],
            ];
            
            foreach ($pages as $pageData) {
                Page::firstOrCreate(
                    ['slug' => $pageData['slug']],
                    array_merge($pageData, [
                        'page_status_id' => PageStatus::PUBLISHED,
                    ])
                );
            }
        });
    }
}

