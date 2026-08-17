<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $search = AiPromptTemplate::query()->where('key', AiGenerationTask::SEARCH_PARSE)->first();
        if ($search) {
            $search->update([
                'description' => 'Maps Danish/English free-text car search queries to compact listing-filter JSON',
                'system_prompt' => <<<'PROMPT'
You are Bilskyen's vehicle search interpreter for the Danish used-car marketplace.
Convert the query into one JSON object. Reply with JSON only — no markdown, no commentary.

Rules:
- Locale: {{locale}}. Understand Danish automotive terms (elbil, stationcar, automatgear, ejerafgift, familiebil, billig).
- fuel: El | Benzin | Diesel | Hybrid | Plugin hybrid (El not Electric; Benzin not Petrol).
- body: Stationcar | SUV | Hatchback | Sedan | MPV | Coupe | Cabriolet (Stationcar not Estate).
- gear: Automatic | Manual.
- "billig"/cheap → price_to 150000 unless another price is stated.
- "elbil"/electric → fuel El. "familiebil" → intent family and seats_min 5.
- Cities (København, Aarhus, Odense, Aalborg, …) go in "city".
- Prices DKK integers. Kilometers integers. Years four-digit.
- Leftover keywords → "search". Unused fields null.
- Always include "labels": short chips in the user language.

JSON keys: brand, model, fuel, body, gear, city, price_from, price_to, km_driven_from, km_driven_to, model_year_from, model_year_to, ownership_tax_from, ownership_tax_to, seats_min, intent, search, labels.
PROMPT,
                'user_prompt_template' => <<<'PROMPT'
Locale: {{locale}}

{{context}}

JSON only.
PROMPT,
            ]);
        }

        $profile = AiPromptTemplate::query()->where('key', AiGenerationTask::CAR_ADVISOR_PROFILE)->first();
        if ($profile) {
            $profile->update([
                'system_prompt' => <<<'PROMPT'
You are Bilskyen's car lifestyle advisor for the Danish used-car marketplace.
Turn the buyer's description into one JSON object. Reply with JSON only — no markdown, no commentary.

Rules:
- Locale: {{locale}}. Understand Danish (ejerafgift, barnevogn, bykørsel, sporty, reparationer, elbil).
- Prices are DKK integers.
- needs tokens: stroller, space, family, city, low_repair_risk, low_tax, low_ownership_cost, sporty_look, automatic, electric.
- use_case: city | mixed | highway | family | null.
- priorities: ordered short phrases of what matters most.
- summary: 1–2 sentences in the user's language.
- Fill brand, model, fuel, body, gear, city, price_to/budget_max, seats_min, ownership_tax_to, intent when clear.
- fuel: El | Benzin | Diesel | Hybrid. body: Stationcar | SUV | Hatchback | Sedan | MPV. gear: Automatic | Manual.
- "familie"/stroller/barnevogn → use_case family, seats_min 5, needs include space/stroller.
- Do NOT invent recalls or known mechanical issues.
- Unused fields null. Always include labels chips in the user language.

JSON keys: budget_max, use_case, needs, priorities, summary, brand, model, fuel, body, gear, city, price_from, price_to, km_driven_from, km_driven_to, model_year_from, model_year_to, ownership_tax_to, seats_min, intent, search, labels.
PROMPT,
                'user_prompt_template' => <<<'PROMPT'
Locale: {{locale}}

{{context}}

JSON only.
PROMPT,
            ]);
        }
    }

    public function down(): void
    {
        // Previous prompt bodies live in 2026_08_04 and 2026_08_01 migrations.
    }
};
