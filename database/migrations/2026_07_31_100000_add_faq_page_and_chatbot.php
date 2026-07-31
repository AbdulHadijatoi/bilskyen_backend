<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use App\Models\PageContent;
use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = app(PlatformSettingService::class);

        if ($settings->get('general', 'faq_page_enabled') === null) {
            $settings->set('general', 'faq_page_enabled', 'true');
        }

        if ($settings->get('general', 'faq_chatbot_enabled') === null) {
            $settings->set('general', 'faq_chatbot_enabled', 'false');
        }

        $this->seedFaqPageContent();
        $this->seedFaqChatPrompt();
    }

    public function down(): void
    {
        $settings = app(PlatformSettingService::class);
        $settings->set('general', 'faq_page_enabled', null);
        $settings->set('general', 'faq_chatbot_enabled', null);

        PageContent::query()->where('page_name', 'faq')->delete();
        AiPromptTemplate::query()->where('key', AiGenerationTask::FAQ_CHAT)->delete();
    }

    private function seedFaqPageContent(): void
    {
        if (PageContent::query()->where('page_name', 'faq')->where('section_key', 'faq_sections_json')->exists()) {
            return;
        }

        $sections = [
            [
                'id' => 'getting-started',
                'title' => 'Getting started',
                'order' => 0,
                'items' => [
                    [
                        'id' => 'gs-1',
                        'question' => 'What is Bilskyen?',
                        'answer' => 'Bilskyen is a Danish car marketplace where you can browse vehicles from dealers and private sellers, contact sellers, and manage listings through our dealer and seller tools.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'gs-2',
                        'question' => 'How do I browse vehicles?',
                        'answer' => 'Go to Vehicles in the main navigation. Use filters for brand, model, price, year, fuel type, body type, and more. You can also explore cars by city from the footer or city hub pages.',
                        'order' => 1,
                    ],
                    [
                        'id' => 'gs-3',
                        'question' => 'Can I switch language?',
                        'answer' => 'Yes. When the language switcher is enabled by the platform admin, you can switch between Danish and English on the public site and in the panel.',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'id' => 'buying',
                'title' => 'Buying a car',
                'order' => 1,
                'items' => [
                    [
                        'id' => 'buy-1',
                        'question' => 'How do I contact a dealer about a vehicle?',
                        'answer' => 'Open the vehicle detail page and use the enquiry or contact form. Your message creates a lead for the dealer so they can follow up. You can also use WhatsApp or phone links when the dealer has published them.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'buy-2',
                        'question' => 'What is a vehicle trust report?',
                        'answer' => 'Some listings show a trust or inspection-related report when the dealer has provided inspection data and the marketplace trust report feature is enabled. It helps you review condition and transparency before contacting the seller.',
                        'order' => 1,
                    ],
                    [
                        'id' => 'buy-3',
                        'question' => 'Can I estimate monthly payments?',
                        'answer' => 'When the finance calculator is enabled, vehicle pages may show an estimated monthly payment based on price and platform rate settings. Estimates are illustrative only and not a binding credit offer.',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'id' => 'selling',
                'title' => 'Selling a car',
                'order' => 2,
                'items' => [
                    [
                        'id' => 'sell-1',
                        'question' => 'How do I sell my car as a private seller?',
                        'answer' => 'Use Sell Your Car from the navigation. Sign in or create an account, then complete the listing form with vehicle details, photos, and pricing. You can manage your listings from My Listings after publishing.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'sell-2',
                        'question' => 'Can AI help write my listing description?',
                        'answer' => 'On Sell Your Car, when AI is enabled by the platform admin, you can generate a listing description from your vehicle details. Review and edit the text before submitting.',
                        'order' => 1,
                    ],
                ],
            ],
            [
                'id' => 'dealers',
                'title' => 'For dealers',
                'order' => 3,
                'items' => [
                    [
                        'id' => 'dealer-1',
                        'question' => 'How do dealers use Bilskyen?',
                        'answer' => 'Dealers manage inventory, leads, enquiries, and subscription features from the dealer panel. Visit For Dealers to learn about plans and pricing, then register or contact us to get started.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'dealer-2',
                        'question' => 'What subscription features are available?',
                        'answer' => 'Plans can include lead management, AI assistant tools, enquiry follow-ups, listing health insights, and other marketplace features. Exact entitlements depend on the active plan assigned to the dealer.',
                        'order' => 1,
                    ],
                    [
                        'id' => 'dealer-3',
                        'question' => 'Where can staff work?',
                        'answer' => 'Dealership staff can access the staff panel when invited by their dealer, with permissions controlled by the dealer admin.',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'id' => 'accounts',
                'title' => 'Accounts & security',
                'order' => 4,
                'items' => [
                    [
                        'id' => 'acc-1',
                        'question' => 'How do I create an account?',
                        'answer' => 'Use Sign Up from the header. Choose the appropriate flow for buyers/sellers or dealers. After registration you can sign in and access your profile, favorites, and listings.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'acc-2',
                        'question' => 'How do I delete my account?',
                        'answer' => 'See the Account deletion page linked in the footer for step-by-step instructions that meet mobile store and privacy requirements.',
                        'order' => 1,
                    ],
                    [
                        'id' => 'acc-3',
                        'question' => 'How is my data protected?',
                        'answer' => 'We describe data practices in our Privacy Policy. You can also learn about GDPR export options and retention in our legal pages when those features are enabled by the platform.',
                        'order' => 2,
                    ],
                ],
            ],
            [
                'id' => 'enquiries',
                'title' => 'Enquiries & leads',
                'order' => 5,
                'items' => [
                    [
                        'id' => 'enq-1',
                        'question' => 'What happens after I send an enquiry?',
                        'answer' => 'The dealer receives your message as a lead in their panel. They may reply by email, phone, or WhatsApp depending on how they work. Automated follow-up sequences may run if enabled for that dealer.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'enq-2',
                        'question' => 'I did not finish a form — will I get a reminder?',
                        'answer' => 'If abandoned enquiry reminders are enabled on the platform, you may receive a follow-up when a form was started but not completed. You can unsubscribe from marketing emails when offered.',
                        'order' => 1,
                    ],
                ],
            ],
            [
                'id' => 'privacy-trust',
                'title' => 'Privacy & trust',
                'order' => 6,
                'items' => [
                    [
                        'id' => 'priv-1',
                        'question' => 'Where are the Privacy Policy and Terms?',
                        'answer' => 'Both are linked in the site footer: Privacy Policy and Terms of Service. Read them for legal terms of using Bilskyen.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'priv-2',
                        'question' => 'Do you use cookies?',
                        'answer' => 'When cookie consent is enabled, a consent banner appears so you can accept or manage non-essential cookies according to platform settings.',
                        'order' => 1,
                    ],
                ],
            ],
            [
                'id' => 'more-help',
                'title' => 'Getting more help',
                'order' => 7,
                'items' => [
                    [
                        'id' => 'help-1',
                        'question' => 'How do I contact support?',
                        'answer' => 'Use the Contact page to send a message to our team. You can also find email and phone details in the footer when published.',
                        'order' => 0,
                    ],
                    [
                        'id' => 'help-2',
                        'question' => 'Can the FAQ chatbot answer everything?',
                        'answer' => 'When the FAQ chatbot is enabled, it answers using the FAQ content on this page and the active AI provider configured by the admin. If it cannot help, contact us via the Contact page.',
                        'order' => 1,
                    ],
                ],
            ],
        ];

        PageContent::updateOrCreate(
            ['page_name' => 'faq', 'section_key' => 'faq_header_title'],
            ['content' => 'Help & FAQ']
        );
        PageContent::updateOrCreate(
            ['page_name' => 'faq', 'section_key' => 'faq_header_description'],
            ['content' => 'Find answers about browsing, buying, selling, dealer tools, and your account on Bilskyen.']
        );
        PageContent::updateOrCreate(
            ['page_name' => 'faq', 'section_key' => 'faq_sections_json'],
            ['content' => json_encode($sections, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function seedFaqChatPrompt(): void
    {
        AiPromptTemplate::firstOrCreate(
            ['key' => AiGenerationTask::FAQ_CHAT],
            [
                'name' => 'FAQ support chatbot',
                'description' => 'Answers public FAQ chat questions using only the provided FAQ knowledge base',
                'system_prompt' => 'You are the Bilskyen help assistant on the FAQ page. Answer ONLY using the FAQ knowledge base provided in the context. If the answer is not in the knowledge base, say you do not know and suggest the Contact page (/contact) or browsing the FAQ sections on this page. Do not invent product features, prices, or policies. Keep answers concise and helpful. Match the user language; locale hint: {{locale}}.',
                'user_prompt_template' => "Locale: {{locale}}\n\n{{context}}\n\nAnswer the User message using only the knowledge base. If conversation history is provided, stay consistent with it.",
                'sort_order' => 90,
                'is_active' => true,
            ]
        );
    }
};
