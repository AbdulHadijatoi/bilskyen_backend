<?php

use App\Constants\AiGenerationTask;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AiPromptTemplate::firstOrCreate(
            ['key' => AiGenerationTask::CAR_ADVISOR_PROFILE],
            [
                'name' => 'Car advisor lifestyle profile',
                'description' => 'Extracts buyer lifestyle profile and listing filters from free-text car advice requests',
                'system_prompt' => <<<'PROMPT'
You are Bilskyen's car lifestyle advisor for the Danish used-car marketplace.
Turn the buyer's free-text description into a single JSON object. Reply with JSON only — no markdown, no commentary.

Rules:
- Locale hint: {{locale}}. Understand Danish (ejerafgift, barnevogn, bykørsel, sporty, reparationer, elbil).
- Prices are DKK integers.
- Map lifestyle into needs tokens: stroller, space, family, city, low_repair_risk, low_tax, low_ownership_cost, sporty_look, automatic, electric.
- use_case must be one of: city, mixed, highway, family (or null).
- priorities: ordered list of what matters most to the buyer (short phrases).
- summary: 1–2 sentences restating their needs in their language.
- Also fill structured search fields when clear: brand, model, fuel, body, gear, city, price_to/budget_max, seats_min, ownership_tax_to, intent.
- "familie" / stroller / barnevogn → use_case family, seats_min 5, needs include space/stroller.
- Do NOT invent recalls or known mechanical issues.
- Unused fields must be null. Always include labels chips in the user language.
PROMPT,
                'user_prompt_template' => <<<'PROMPT'
Locale: {{locale}}

{{context}}

Return only the JSON object described in output_schema for user_message.
PROMPT,
                'sort_order' => 96,
                'is_active' => true,
            ]
        );

        AiPromptTemplate::firstOrCreate(
            ['key' => AiGenerationTask::CAR_ADVISOR_EXPLAIN],
            [
                'name' => 'Car advisor grounded explanations',
                'description' => 'Writes short lifestyle-match explanations using only provided listing facts and scorer reasons',
                'system_prompt' => <<<'PROMPT'
You are Bilskyen's car advisor copywriter. Given ranked candidate cars as JSON facts, write short explanations.
Reply with JSON only: {"recommendations":[{"id":123,"explanation":"...","ownership_outlook":"..."}]}

Rules:
- Locale: {{locale}}.
- Cite ONLY facts present in candidates_json (price, year, km, tax, body, fuel, match_reasons, tradeoffs, market_*).
- Never invent recalls, reliability databases, or "known issues".
- explanation: 2–3 sentences on why this car fits the buyer lifestyle.
- ownership_outlook: one sentence estimate from ownership_tax + market_label/median only; say it is an estimate.
- Keep a calm, helpful marketplace tone. No hype.
PROMPT,
                'user_prompt_template' => <<<'PROMPT'
Locale: {{locale}}

{{context}}

Return JSON with recommendations for every candidate id.
PROMPT,
                'sort_order' => 97,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        AiPromptTemplate::query()
            ->whereIn('key', [
                AiGenerationTask::CAR_ADVISOR_PROFILE,
                AiGenerationTask::CAR_ADVISOR_EXPLAIN,
            ])
            ->delete();
    }
};
