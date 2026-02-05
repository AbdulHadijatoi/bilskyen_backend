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
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate plan_features first (due to foreign key constraint)
        DB::table('plan_features')->truncate();
        
        // Truncate features table
        DB::table('features')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // Ensure feature value types exist before inserting features
        // This is critical for production where seeders may not have run
        $featureValueTypes = [
            ['id' => FeatureValueType::BOOLEAN, 'name' => 'Boolean'],
            ['id' => FeatureValueType::NUMBER, 'name' => 'Number'],
            ['id' => FeatureValueType::TEXT, 'name' => 'Text'],
        ];

        foreach ($featureValueTypes as $type) {
            DB::table('feature_value_types')->updateOrInsert(
                ['id' => $type['id']],
                ['name' => $type['name']]
            );
        }

        // Use the constants directly since IDs are fixed
        $booleanTypeId = FeatureValueType::BOOLEAN; // 1
        $numberTypeId = FeatureValueType::NUMBER; // 2
        $textTypeId = FeatureValueType::TEXT; // 3

        $features = [
            [
                'key' => 'max_listings',
                'feature_value_type_id' => $numberTypeId,
                'description' => 'Maximum number of vehicle listings allowed',
                'created_at' => now(),
            ],
            [
                'key' => 'enquiry_management',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Access to enquiry management features',
                'created_at' => now(),
            ],
            [
                'key' => 'lead_management',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Access to lead management features',
                'created_at' => now(),
            ],
            [
                'key' => 'staff_management',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Access to staff management features',
                'created_at' => now(),
            ],
            [
                'key' => 'max_feature_listings',
                'feature_value_type_id' => $numberTypeId,
                'description' => 'Maximum number of featured vehicle listings',
                'created_at' => now(),
            ],
            [
                'key' => 'priority_support',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Priority customer support access',
                'created_at' => now(),
            ],
            [
                'key' => 'analytics',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Access to analytics dashboard',
                'created_at' => now(),
            ],
            [
                'key' => 'max_vehicle_images',
                'feature_value_type_id' => $numberTypeId,
                'description' => 'Maximum number of images per vehicle',
                'created_at' => now(),
            ],
            [
                'key' => 'upload_3d_view',
                'feature_value_type_id' => $booleanTypeId,
                'description' => 'Ability to upload 3D views of vehicles',
                'created_at' => now(),
            ],
        ];

        DB::table('features')->insert($features);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('plan_features')->truncate();
        DB::table('features')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
