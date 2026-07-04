<?php

use App\Models\FeatureValueType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, string>> */
    private array $planMatrix = [
        'basic' => [
            'max_listings' => '10',
            'max_vehicle_images' => '10',
            'max_equipment_per_vehicle' => '10',
            'max_feature_listings' => '2',
            'max_staff_users' => '1',
            'enquiry_management' => 'true',
            'lead_management' => 'true',
            'staff_management' => 'true',
            'analytics' => 'false',
            'upload_3d_view' => 'false',
            'audit_logs' => 'false',
            'priority_support' => 'false',
            'listing_health_inbox' => 'true',
            'listing_health_actions' => 'false',
            'listing_health_ai_fixes' => 'false',
            'listing_health_ai_briefing' => 'false',
            'listing_health_equipment_gap' => 'false',
            'listing_health_price_apply' => 'false',
            'listing_health_before_after' => 'false',
            'market_pulse' => 'true',
            'pricing_intelligence' => 'false',
            'price_change_alerts' => 'false',
            'auto_feature_listings' => 'false',
            'listing_boost' => 'false',
            'premium_dealer_badge' => 'false',
            'ai_assistant' => 'false',
            'ai_monthly_requests' => '0',
            'enquiry_ai_replies' => 'false',
            'lead_ai_summary' => 'false',
            'advanced_analytics' => 'false',
            'analytics_pdf_export' => 'false',
            'analytics_listing_funnel' => 'false',
            'analytics_dealer_benchmark' => 'false',
            'lead_auto_assign' => 'false',
            'lead_sla_alerts' => 'false',
            'lead_task_board' => 'false',
            'inventory_feeds' => 'false',
            'syndication' => 'false',
            'syndication_channels' => '0',
            'dms_sync' => 'false',
            'bulk_price_update' => 'false',
            'api_access' => 'false',
            'marketing_campaigns' => 'false',
            'retargeting' => 'false',
            'finance_calculator_dealer' => 'false',
            'deal_builder' => 'false',
            'review_management' => 'false',
            'dealer_trust_badge' => 'false',
            'branded_inventory_audit' => 'false',
        ],
        'professional' => [
            'max_listings' => '50',
            'max_vehicle_images' => '20',
            'max_equipment_per_vehicle' => '30',
            'max_feature_listings' => '10',
            'max_staff_users' => '5',
            'enquiry_management' => 'true',
            'lead_management' => 'true',
            'staff_management' => 'true',
            'analytics' => 'true',
            'upload_3d_view' => 'true',
            'audit_logs' => 'true',
            'priority_support' => 'true',
            'listing_health_inbox' => 'true',
            'listing_health_actions' => 'true',
            'listing_health_ai_fixes' => 'false',
            'listing_health_ai_briefing' => 'false',
            'listing_health_equipment_gap' => 'true',
            'listing_health_price_apply' => 'false',
            'listing_health_before_after' => 'false',
            'market_pulse' => 'true',
            'pricing_intelligence' => 'true',
            'price_change_alerts' => 'false',
            'auto_feature_listings' => 'false',
            'listing_boost' => 'false',
            'premium_dealer_badge' => 'false',
            'ai_assistant' => 'true',
            'ai_monthly_requests' => '50',
            'enquiry_ai_replies' => 'true',
            'lead_ai_summary' => 'true',
            'advanced_analytics' => 'false',
            'analytics_pdf_export' => 'false',
            'analytics_listing_funnel' => 'true',
            'analytics_dealer_benchmark' => 'true',
            'lead_auto_assign' => 'false',
            'lead_sla_alerts' => 'false',
            'lead_task_board' => 'true',
            'inventory_feeds' => 'true',
            'syndication' => 'false',
            'syndication_channels' => '0',
            'dms_sync' => 'false',
            'bulk_price_update' => 'true',
            'api_access' => 'false',
            'marketing_campaigns' => 'false',
            'retargeting' => 'false',
            'finance_calculator_dealer' => 'true',
            'deal_builder' => 'false',
            'review_management' => 'false',
            'dealer_trust_badge' => 'true',
            'branded_inventory_audit' => 'false',
        ],
        'premium' => [
            'max_listings' => '200',
            'max_vehicle_images' => '30',
            'max_equipment_per_vehicle' => '50',
            'max_feature_listings' => '25',
            'max_staff_users' => '20',
            'enquiry_management' => 'true',
            'lead_management' => 'true',
            'staff_management' => 'true',
            'analytics' => 'true',
            'upload_3d_view' => 'true',
            'audit_logs' => 'true',
            'priority_support' => 'true',
            'listing_health_inbox' => 'true',
            'listing_health_actions' => 'true',
            'listing_health_ai_fixes' => 'true',
            'listing_health_ai_briefing' => 'true',
            'listing_health_equipment_gap' => 'true',
            'listing_health_price_apply' => 'true',
            'listing_health_before_after' => 'true',
            'market_pulse' => 'true',
            'pricing_intelligence' => 'true',
            'price_change_alerts' => 'true',
            'auto_feature_listings' => 'true',
            'listing_boost' => 'true',
            'premium_dealer_badge' => 'true',
            'ai_assistant' => 'true',
            'ai_monthly_requests' => '300',
            'enquiry_ai_replies' => 'true',
            'lead_ai_summary' => 'true',
            'advanced_analytics' => 'true',
            'analytics_pdf_export' => 'true',
            'analytics_listing_funnel' => 'true',
            'analytics_dealer_benchmark' => 'true',
            'lead_auto_assign' => 'true',
            'lead_sla_alerts' => 'true',
            'lead_task_board' => 'true',
            'inventory_feeds' => 'true',
            'syndication' => 'true',
            'syndication_channels' => '3',
            'dms_sync' => 'true',
            'bulk_price_update' => 'true',
            'api_access' => 'true',
            'marketing_campaigns' => 'true',
            'retargeting' => 'true',
            'finance_calculator_dealer' => 'true',
            'deal_builder' => 'true',
            'review_management' => 'true',
            'dealer_trust_badge' => 'true',
            'branded_inventory_audit' => 'true',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('features') || ! Schema::hasTable('plans')) {
            return;
        }

        $booleanTypeId = FeatureValueType::BOOLEAN;
        $numberTypeId = FeatureValueType::NUMBER;

        $newFeatures = [
            ['key' => 'max_staff_users', 'type' => $numberTypeId, 'label_en' => 'Max staff users', 'label_da' => 'Maks. medarbejdere'],
            ['key' => 'listing_health_inbox', 'type' => $booleanTypeId, 'label_en' => 'Listing health inbox', 'label_da' => 'Annoncesundhed inbox'],
            ['key' => 'listing_health_actions', 'type' => $booleanTypeId, 'label_en' => 'Listing quick actions', 'label_da' => 'Hurtige annoncehandlinger'],
            ['key' => 'listing_health_ai_fixes', 'type' => $booleanTypeId, 'label_en' => 'AI listing fixes', 'label_da' => 'AI-annoncerettelser'],
            ['key' => 'listing_health_ai_briefing', 'type' => $booleanTypeId, 'label_en' => 'AI weekly briefing', 'label_da' => 'AI ugentlig briefing'],
            ['key' => 'listing_health_equipment_gap', 'type' => $booleanTypeId, 'label_en' => 'Equipment gap alerts', 'label_da' => 'Udstyrsgab-advarsler'],
            ['key' => 'listing_health_price_apply', 'type' => $booleanTypeId, 'label_en' => 'Apply suggested price', 'label_da' => 'Anvend foreslået pris'],
            ['key' => 'listing_health_before_after', 'type' => $booleanTypeId, 'label_en' => 'Fix impact reports', 'label_da' => 'Rapporter for fix-effekt'],
            ['key' => 'market_pulse', 'type' => $booleanTypeId, 'label_en' => 'Market pulse', 'label_da' => 'Markeds-puls'],
            ['key' => 'pricing_intelligence', 'type' => $booleanTypeId, 'label_en' => 'Pricing intelligence', 'label_da' => 'Pris-intelligens'],
            ['key' => 'price_change_alerts', 'type' => $booleanTypeId, 'label_en' => 'Price change alerts', 'label_da' => 'Prisændrings-advarsler'],
            ['key' => 'auto_feature_listings', 'type' => $booleanTypeId, 'label_en' => 'Auto-feature listings', 'label_da' => 'Auto-fremhæv annoncer'],
            ['key' => 'listing_boost', 'type' => $booleanTypeId, 'label_en' => 'Listing boost', 'label_da' => 'Annonce-boost'],
            ['key' => 'premium_dealer_badge', 'type' => $booleanTypeId, 'label_en' => 'Premium dealer badge', 'label_da' => 'Premium-forhandler badge'],
            ['key' => 'enquiry_ai_replies', 'type' => $booleanTypeId, 'label_en' => 'AI enquiry replies', 'label_da' => 'AI-henvendelsessvar'],
            ['key' => 'lead_ai_summary', 'type' => $booleanTypeId, 'label_en' => 'AI lead summaries', 'label_da' => 'AI-leadresuméer'],
            ['key' => 'advanced_analytics', 'type' => $booleanTypeId, 'label_en' => 'Advanced analytics', 'label_da' => 'Avanceret analyse'],
            ['key' => 'analytics_pdf_export', 'type' => $booleanTypeId, 'label_en' => 'Analytics PDF export', 'label_da' => 'Analyse PDF-eksport'],
            ['key' => 'analytics_listing_funnel', 'type' => $booleanTypeId, 'label_en' => 'Listing funnel analytics', 'label_da' => 'Annonce-tragtanalyse'],
            ['key' => 'analytics_dealer_benchmark', 'type' => $booleanTypeId, 'label_en' => 'Dealer benchmark', 'label_da' => 'Forhandler-benchmark'],
            ['key' => 'lead_auto_assign', 'type' => $booleanTypeId, 'label_en' => 'Lead auto-assign', 'label_da' => 'Auto-tildeling af leads'],
            ['key' => 'lead_sla_alerts', 'type' => $booleanTypeId, 'label_en' => 'Lead SLA alerts', 'label_da' => 'Lead SLA-advarsler'],
            ['key' => 'lead_task_board', 'type' => $booleanTypeId, 'label_en' => 'Lead task board', 'label_da' => 'Lead opgaveboard'],
            ['key' => 'inventory_feeds', 'type' => $booleanTypeId, 'label_en' => 'Inventory feeds', 'label_da' => 'Lager-feeds'],
            ['key' => 'syndication', 'type' => $booleanTypeId, 'label_en' => 'Syndication', 'label_da' => 'Syndikering'],
            ['key' => 'syndication_channels', 'type' => $numberTypeId, 'label_en' => 'Syndication channels', 'label_da' => 'Syndikeringskanaler'],
            ['key' => 'dms_sync', 'type' => $booleanTypeId, 'label_en' => 'DMS sync', 'label_da' => 'DMS-synkronisering'],
            ['key' => 'bulk_price_update', 'type' => $booleanTypeId, 'label_en' => 'Bulk price update', 'label_da' => 'Masseopdatering af priser'],
            ['key' => 'api_access', 'type' => $booleanTypeId, 'label_en' => 'API access', 'label_da' => 'API-adgang'],
            ['key' => 'marketing_campaigns', 'type' => $booleanTypeId, 'label_en' => 'Marketing campaigns', 'label_da' => 'Marketingkampagner'],
            ['key' => 'retargeting', 'type' => $booleanTypeId, 'label_en' => 'Retargeting', 'label_da' => 'Retargeting'],
            ['key' => 'finance_calculator_dealer', 'type' => $booleanTypeId, 'label_en' => 'Finance calculator', 'label_da' => 'Finansberegner'],
            ['key' => 'deal_builder', 'type' => $booleanTypeId, 'label_en' => 'Deal builder', 'label_da' => 'Deal builder'],
            ['key' => 'review_management', 'type' => $booleanTypeId, 'label_en' => 'Review management', 'label_da' => 'Anmeldelsesstyring'],
            ['key' => 'dealer_trust_badge', 'type' => $booleanTypeId, 'label_en' => 'Trust badge', 'label_da' => 'Tillidsbadge'],
            ['key' => 'branded_inventory_audit', 'type' => $booleanTypeId, 'label_en' => 'Branded inventory audit', 'label_da' => 'Branded lager-audit'],
        ];

        foreach ($newFeatures as $feature) {
            $existing = DB::table('features')->where('key', $feature['key'])->first();
            if ($existing) {
                DB::table('features')->where('id', $existing->id)->update([
                    'label_en' => $feature['label_en'],
                    'label_da' => $feature['label_da'],
                ]);
            } else {
                DB::table('features')->insert([
                    'key' => $feature['key'],
                    'feature_value_type_id' => $feature['type'],
                    'description' => $feature['label_en'],
                    'label_en' => $feature['label_en'],
                    'label_da' => $feature['label_da'],
                    'created_at' => now(),
                ]);
            }
        }

        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        if (! $dealerRole) {
            return;
        }

        $this->renamePaygPlans();
        $this->ensurePremiumPlan($dealerRole->id);
        $this->syncAllPlanFeatures();
    }

    private function ensurePremiumPlan(int $dealerRoleId): void
    {
        $existing = DB::table('plans')->where('slug', 'premium')->first();
        if ($existing) {
            $planId = $existing->id;
        } else {
            $planId = DB::table('plans')->insertGetId([
                'name' => 'Premium Plan',
                'slug' => 'premium',
                'description' => 'Full visibility, AI fixes, syndication, and advanced tools for high-volume dealers',
                'is_active' => true,
                'trial_days' => 14,
                'billing_model' => 'subscription',
                'price_per_listing_per_day' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('plan_price_history')->insert([
                [
                    'plan_id' => $planId,
                    'price' => 29900,
                    'currency' => 'DKK',
                    'billing_cycle' => 'monthly',
                    'starts_at' => now(),
                    'ends_at' => null,
                ],
                [
                    'plan_id' => $planId,
                    'price' => 299000,
                    'currency' => 'DKK',
                    'billing_cycle' => 'yearly',
                    'starts_at' => now(),
                    'ends_at' => null,
                ],
            ]);

            DB::table('plan_availability')->insert([
                'plan_id' => $planId,
                'allowed_role_id' => $dealerRoleId,
                'dealer_id' => null,
                'is_enabled' => true,
                'created_at' => now(),
            ]);
        }

        $premiumPayg = DB::table('plans')->where('slug', 'premium-payg')->first();
        if (! $premiumPayg) {
            $paygId = DB::table('plans')->insertGetId([
                'name' => 'Premium Pay As You Go',
                'slug' => 'premium-payg',
                'description' => 'Premium features with per listing per day billing',
                'is_active' => true,
                'trial_days' => 0,
                'billing_model' => 'usage_daily',
                'price_per_listing_per_day' => 290,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('plan_availability')->insert([
                'plan_id' => $paygId,
                'allowed_role_id' => $dealerRoleId,
                'dealer_id' => null,
                'is_enabled' => true,
                'created_at' => now(),
            ]);
        }
    }

    private function renamePaygPlans(): void
    {
        $oldPremiumPayg = DB::table('plans')->where('slug', 'premium-payg')->first();
        $professionalPayg = DB::table('plans')->where('slug', 'professional-payg')->first();

        if ($oldPremiumPayg && ! $professionalPayg && $oldPremiumPayg->billing_model === 'usage_daily') {
            $name = $oldPremiumPayg->name;
            if (str_contains(strtolower($name), 'premium')) {
                DB::table('plans')->where('id', $oldPremiumPayg->id)->update([
                    'name' => 'Professional Pay As You Go',
                    'slug' => 'professional-payg',
                    'description' => 'Professional features with per listing per day billing',
                    'price_per_listing_per_day' => 240,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function syncAllPlanFeatures(): void
    {
        $features = DB::table('features')->get()->keyBy('key');
        $paygOverrides = [
            'basic-payg' => 'basic',
            'professional-payg' => 'professional',
            'premium-payg' => 'premium',
        ];

        foreach (array_merge(array_keys($this->planMatrix), array_keys($paygOverrides)) as $planSlug) {
            $plan = DB::table('plans')->where('slug', $planSlug)->first();
            if (! $plan) {
                continue;
            }

            $matrixKey = $paygOverrides[$planSlug] ?? $planSlug;
            $values = $this->planMatrix[$matrixKey] ?? null;
            if ($values === null) {
                continue;
            }

            if (in_array($planSlug, ['basic-payg', 'professional-payg', 'premium-payg'], true)) {
                $values['max_listings'] = '9999';
            }

            foreach ($values as $featureKey => $value) {
                $feature = $features->get($featureKey);
                if (! $feature) {
                    continue;
                }

                DB::table('plan_features')->updateOrInsert(
                    ['plan_id' => $plan->id, 'feature_id' => $feature->id],
                    ['value' => $value]
                );
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: feature keys remain for data integrity
    }
};
