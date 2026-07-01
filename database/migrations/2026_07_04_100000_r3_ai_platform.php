<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use App\Models\Feature;
use App\Models\FeatureValueType;
use App\Services\PlatformSettingService;
use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
                $table->string('provider', 32)->index();
                $table->string('model', 128)->nullable();
                $table->string('task', 64)->index();
                $table->unsignedInteger('prompt_tokens')->default(0);
                $table->unsignedInteger('completion_tokens')->default(0);
                $table->string('context_type', 64)->nullable();
                $table->unsignedBigInteger('context_id')->nullable();
                $table->string('status', 32)->default('success')->index();
                $table->text('error_message')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        if (! Schema::hasTable('ai_prompt_templates')) {
            Schema::create('ai_prompt_templates', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->text('system_prompt');
                $table->text('user_prompt_template');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $permissions = [
            'dealer.ai.use',
            'staff.ai.use',
            'admin.ai.view',
            'admin.ai.manage',
        ];

        $rolePermissionService = app(RolePermissionService::class);
        foreach ($permissions as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(['admin.ai.view', 'admin.ai.manage']);
        }

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo('dealer.ai.use');
        }

        $staffRole = Role::where('name', 'staff')->where('guard_name', 'web')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo('staff.ai.use');
        }

        $booleanType = FeatureValueType::find(FeatureValueType::BOOLEAN);
        $numberType = FeatureValueType::find(FeatureValueType::NUMBER);
        if ($booleanType && $numberType) {
            Feature::firstOrCreate(
                ['key' => 'ai_assistant'],
                [
                    'feature_value_type_id' => $booleanType->id,
                    'description' => 'AI writing assistant for listings and CRM',
                    'created_at' => now(),
                ]
            );
            Feature::firstOrCreate(
                ['key' => 'ai_monthly_requests'],
                [
                    'feature_value_type_id' => $numberType->id,
                    'description' => 'Monthly AI generation request quota per dealer',
                    'created_at' => now(),
                ]
            );
        }

        $this->seedPromptTemplates();
        $this->seedAiSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_prompt_templates');
    }

    private function seedPromptTemplates(): void
    {
        $templates = [
            [
                'key' => AiGenerationTask::VEHICLE_DESCRIPTION,
                'name' => 'Vehicle listing description',
                'description' => 'Full marketing description for a dealer vehicle listing',
                'system_prompt' => 'You are an expert automotive copywriter for a Danish used-car marketplace (Bilskyen). Write clear, trustworthy listings. Output language must match the locale: Danish (da) or English (en). Do not invent facts not present in the context. No markdown headings.',
                'user_prompt_template' => "Locale: {{locale}}\n\nVehicle data:\n{{context}}\n\nWrite a compelling vehicle listing description (2-4 short paragraphs). Mention key equipment and condition honestly. End with a soft call-to-action to contact the dealer.",
                'sort_order' => 10,
            ],
            [
                'key' => AiGenerationTask::VEHICLE_TITLE,
                'name' => 'Vehicle listing title',
                'description' => 'Short listing title',
                'system_prompt' => 'You write concise vehicle listing titles for a Danish marketplace. One line only. No quotes. Language matches locale (da/en).',
                'user_prompt_template' => "Locale: {{locale}}\n\nVehicle data:\n{{context}}\n\nWrite a single listing title (max 80 characters).",
                'sort_order' => 20,
            ],
            [
                'key' => AiGenerationTask::VEHICLE_HIGHLIGHTS,
                'name' => 'Vehicle highlight bullets',
                'description' => 'Bullet highlights for listing',
                'system_prompt' => 'You extract selling points as bullet lines for a car listing. Use "• " prefix per line. 4-8 bullets. Language matches locale. Only facts from context.',
                'user_prompt_template' => "Locale: {{locale}}\n\nVehicle data:\n{{context}}\n\nList highlight bullets.",
                'sort_order' => 30,
            ],
            [
                'key' => AiGenerationTask::SEO_META,
                'name' => 'SEO meta',
                'description' => 'Meta title and description',
                'system_prompt' => 'You write SEO meta title and description for a vehicle page. Output exactly two lines: "Title: ..." and "Description: ...". Language matches locale.',
                'user_prompt_template' => "Locale: {{locale}}\n\nVehicle data:\n{{context}}\n\nWrite SEO meta title (max 60 chars) and meta description (max 155 chars).",
                'sort_order' => 40,
            ],
            [
                'key' => AiGenerationTask::ENQUIRY_REPLY,
                'name' => 'Enquiry reply suggestion',
                'description' => 'Suggested reply to a customer enquiry',
                'system_prompt' => 'You draft professional dealer replies to vehicle enquiries. Friendly, helpful, concise. Language matches locale. Do not promise price discounts unless stated in context.',
                'user_prompt_template' => "Locale: {{locale}}\n\nContext:\n{{context}}\n\nDraft a reply the dealer can send to the customer.",
                'sort_order' => 50,
            ],
            [
                'key' => AiGenerationTask::LEAD_SUMMARY,
                'name' => 'Lead summary',
                'description' => 'One-paragraph CRM lead summary',
                'system_prompt' => 'You summarize sales leads for dealership staff. One short paragraph. Note vehicle interest, stage, and recommended next action. Language matches locale.',
                'user_prompt_template' => "Locale: {{locale}}\n\nLead data:\n{{context}}\n\nWrite a one-paragraph lead summary for staff.",
                'sort_order' => 60,
            ],
            [
                'key' => AiGenerationTask::LISTING_DESCRIPTION,
                'name' => 'Private seller description',
                'description' => 'Help private sellers write listings',
                'system_prompt' => 'You help private sellers write honest vehicle listings for a Danish marketplace. Language matches locale. 2-3 paragraphs.',
                'user_prompt_template' => "Locale: {{locale}}\n\nVehicle data:\n{{context}}\n\nWrite a listing description a private seller can use.",
                'sort_order' => 70,
            ],
            [
                'key' => AiGenerationTask::CMS_REWRITE,
                'name' => 'CMS copy rewrite',
                'description' => 'Rewrite marketing copy for CMS pages',
                'system_prompt' => 'You rewrite website marketing copy. Keep meaning, improve clarity and tone. Language matches locale.',
                'user_prompt_template' => "Locale: {{locale}}\n\nOriginal copy:\n{{context}}\n\nRewrite for the website.",
                'sort_order' => 80,
            ],
        ];

        foreach ($templates as $template) {
            AiPromptTemplate::firstOrCreate(
                ['key' => $template['key']],
                array_merge($template, ['is_active' => true])
            );
        }
    }

    private function seedAiSettings(): void
    {
        $service = app(PlatformSettingService::class);
        $defaults = [
            'openai_enabled' => 'false',
            'anthropic_enabled' => 'false',
            'gemini_enabled' => 'false',
            'openai_model' => config('ai.providers.openai.default_model'),
            'anthropic_model' => config('ai.providers.anthropic.default_model'),
            'gemini_model' => config('ai.providers.gemini.default_model'),
            'max_tokens' => (string) config('ai.default_max_tokens'),
            'temperature' => (string) config('ai.default_temperature'),
            'monthly_token_budget' => '0',
        ];

        foreach ($defaults as $key => $value) {
            if ($service->get('ai', $key) === null) {
                $service->set('ai', $key, $value);
            }
        }
    }
};
