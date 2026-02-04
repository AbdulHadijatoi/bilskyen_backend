<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\FeatureValueType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $booleanTypeId = FeatureValueType::BOOLEAN; // 1

        // Check if the feature already exists to avoid duplicates
        $existingFeature = DB::table('features')->where('key', 'audit_logs')->first();
        
        if (!$existingFeature) {
            DB::table('features')->insert([
                'key' => 'audit_logs',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Access to audit logs features',
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('features')->where('key', 'audit_logs')->delete();
    }
};
