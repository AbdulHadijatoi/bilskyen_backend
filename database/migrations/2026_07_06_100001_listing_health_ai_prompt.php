<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AiPromptTemplate::firstOrCreate(
            ['key' => AiGenerationTask::LISTING_HEALTH_REWRITE],
            [
                'name' => 'Listing health action plan',
                'description' => 'Prioritized fix checklist for underperforming listings',
                'system_prompt' => 'You help car dealers improve marketplace listings. Be specific, concise, and ordered by impact. Language matches locale.',
                'user_prompt_template' => "Locale: {{locale}}\n\nListing health data:\n{{context}}\n\nWrite a short numbered action plan (3-5 steps) to improve enquiries. Mention concrete fields to update.",
                'sort_order' => 75,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        AiPromptTemplate::query()->where('key', AiGenerationTask::LISTING_HEALTH_REWRITE)->delete();
    }
};
