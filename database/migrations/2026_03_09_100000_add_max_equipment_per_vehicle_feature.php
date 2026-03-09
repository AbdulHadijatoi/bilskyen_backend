<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds max_equipment_per_vehicle feature and plan values for existing databases.
     */
    public function up(): void
    {
        $numberTypeId = \App\Models\FeatureValueType::NUMBER;

        $exists = DB::table('features')->where('key', 'max_equipment_per_vehicle')->exists();
        if (!$exists) {
            DB::table('features')->insert([
                'key' => 'max_equipment_per_vehicle',
                'feature_value_type_id' => $numberTypeId,
                'description' => 'Maximum number of equipment items selectable per vehicle',
                'created_at' => now(),
            ]);
        }

        $feature = DB::table('features')->where('key', 'max_equipment_per_vehicle')->first();
        if (!$feature) {
            return;
        }

        $plans = DB::table('plans')->get();
        foreach ($plans as $plan) {
            $value = match ($plan->slug ?? '') {
                'basic' => '10',
                'professional' => '30',
                'enterprise' => '999',
                'trial' => '5',
                default => '20',
            };
            $exists = DB::table('plan_features')
                ->where('plan_id', $plan->id)
                ->where('feature_id', $feature->id)
                ->exists();
            if (!$exists) {
                DB::table('plan_features')->insert([
                    'plan_id' => $plan->id,
                    'feature_id' => $feature->id,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $feature = DB::table('features')->where('key', 'max_equipment_per_vehicle')->first();
        if ($feature) {
            DB::table('plan_features')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }
    }
};
