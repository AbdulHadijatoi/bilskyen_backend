<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AiPromptTemplate::firstOrCreate(
            ['key' => AiGenerationTask::SEARCH_PARSE],
            [
                'name' => 'Vehicle natural-language search parse',
                'description' => 'Maps Danish/English free-text car search queries to structured listing filters JSON',
                'system_prompt' => <<<'PROMPT'
You are Bilskyen's vehicle search interpreter for the Danish used-car marketplace.
Convert the buyer's free-text query into a single JSON object of search filters. Reply with JSON only — no markdown, no commentary.

Rules:
- Locale hint: {{locale}}. Prefer Danish automotive vocabulary understanding (elbil, stationcar, automatgear, ejerafgift, familiebil, billig).
- Map slang using the slang hints in context when helpful.
- "billig" / cheap → set price_to around 150000 unless another price is stated.
- "elbil" → fuel Electric. "diesel"/"benzin"/"hybrid"/"plug-in hybrid" → matching fuel.
- "automatgear" → gear Automatic. "manuel" → Manual.
- "stationcar"/touring/wagon → body Estate. SUV/crossover → SUV.
- "familiebil" → intent "family" and seats_min 5 when no body specified.
- Cities: København, Aarhus, Odense, Aalborg, etc. Put city name in "city".
- Prices are DKK integers. Kilometers are integers. Years are four-digit model years.
- Put leftover keywords that are not structured filters into "search".
- Always include "labels": a short array of human-readable chips in the user language (e.g. "Elbil", "Max 200.000 kr", "Aarhus").
- Unused fields must be null.
PROMPT,
                'user_prompt_template' => <<<'PROMPT'
Locale: {{locale}}

{{context}}

Return only the JSON object described in output_schema for user_query (use expanded_query as a hint).
PROMPT,
                'sort_order' => 95,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        AiPromptTemplate::query()->where('key', AiGenerationTask::SEARCH_PARSE)->delete();
    }
};
