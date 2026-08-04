<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $template = AiPromptTemplate::query()->where('key', AiGenerationTask::SEARCH_PARSE)->first();
        if (! $template) {
            return;
        }

        $template->update([
            'description' => 'Maps Danish/English free-text car search queries to structured listing filters JSON using DMR catalog names',
            'system_prompt' => <<<'PROMPT'
You are Bilskyen's vehicle search interpreter for the Danish used-car marketplace.
Convert the buyer's free-text query into a single JSON object of search filters. Reply with JSON only — no markdown, no commentary.

Rules:
- Locale hint: {{locale}}. Prefer Danish automotive vocabulary (elbil, stationcar, automatgear, ejerafgift, familiebil, billig).
- Map slang using slang_hints when helpful. expanded_query already normalizes common slang to catalog tokens.
- For fuel, body, and gear fields you MUST use exact names from catalog_fuels, catalog_bodies, and catalog_gears.
  Examples: fuel "El" (not Electric), fuel "Benzin" (not Petrol), body "Stationcar" (not Estate), gear "Automatic" / "Manual".
- "billig" / cheap → set price_to around 150000 unless another price is stated.
- "elbil" / electric / elektrisk → fuel "El".
- "benzin" / petrol → fuel "Benzin". "diesel" → fuel "Diesel".
- "automatgear" / automatisk → gear "Automatic". "manuel" → gear "Manual".
- "stationcar" / estate / touring / wagon → body "Stationcar".
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

Return only the JSON object described in output_schema for user_query (use expanded_query and catalogs as hints).
PROMPT,
        ]);
    }

    public function down(): void
    {
        $template = AiPromptTemplate::query()->where('key', AiGenerationTask::SEARCH_PARSE)->first();
        if (! $template) {
            return;
        }

        // Restore previous English-canonical prompt from 2026_07_31_120000 migration.
        $template->update([
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
        ]);
    }
};
